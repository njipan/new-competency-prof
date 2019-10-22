var sv,listCandidateApp;
var subView = {
    title: 'List Candidate',
    require: 'competency-profiling',
    rel: 'competency-profiling-content',
    onLoaded: function() {
        sv = this;
        sv.onRequire();
        $.fancybox.update();
        let elTitle = $('#competency-profiling-title');
        elTitle.text(elTitle.text().split("-")[0].trim() + ' - ' + sv.title.trim());
    },
    onRequire : function(){
        Promise.all([
            requireScript(BM.baseUri+`newstaff/src/components/nj-dropdown-list.js`,window['njDropdownList']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-datepicker.js`,window['njDatepicker']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-button.js`,window['']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-popup.js`,window['']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-freezepane.js`,window['X']),
            requireScript(BM.baseUri+`newstaff/src/components/tooltip.js`,window['']),
        ])
        .then(function(){
            sv.vue();
        });
    },
    vue : function(){
        axios.defaults.baseURL = BM.serviceUri + 'competency-profiling';
        axios.defaults.headers.post['Content-Type'] = 'application/json';
        axios.interceptors.response.use(
            (response) => (response),
            (error) => {
                if(typeof error.response.data.message == 'string')
                    BM.successMessage(error.response.data.message,'failed', () => {});
                return Promise.reject(error);
            }
        );
        const baseUri = {
            general : `${BM.serviceUri}competency-profiling/general`,
            period : `${BM.serviceUri}competency-profiling/period`
        };
        const initData = {
            ACTION : null,
            ACTION_EDIT : 'edit',
            ACTION_ADD : 'add',
            levelDescs : [],
            levelGrades : [],
            institutions : [],
            organizations : [],
            departments : [],
            periods : [],
            reasons : [],
            candidates : [],
            errors : {},
            form: { 
                institution: {},
                organization: {},
                department: {},
                period : ''
            },
            editForm : {},
            isFormLoading : true,
            isFetchingPeriod : false,
            isSearching : false,
            isPrinting : false,
            popup : {
                note : null,
            },
            searchText : null,
            isSaving : false,
            printData : {},
            candidatesToAdd : [],
            isAddCandidateSearching : false,
            statuses : [],
            STATUS_OPEN : 1,
            STATUS_WAITING : 2,
            STATUS_APPROVED_HOP : 3,
            STATUS_DECLINED_HOP : 4,
            STATUS_DECLINED_LRC : 5,
            STATUS_ON_PROCESS : 6,
            STATUS_ON_REVIEW : 7,
            STATUS_REVIEWED : 8,
            IS_ASKING_UPDATE : false,
            IS_ASKING_ADD : false,
            isPosting : false,
        }
        listCandidateApp = new Vue({
            el: '#list-candidate-app',
            data: initData,
            created: function(){
                _self = this;
                this.isFormLoading = true;
                Promise.all([
                    _self.getIntitutions(), 
                    _self.getOrganizations(), 
                    _self.getDepartments(), 
                    _self.getPeriods(), 
                    _self.getLevelDescs(),
                    _self.getReasons(),
                    _self.getStatuses()
                ]).then(function(responses){
                    var temp = {};
                    $('#competency-profiling-loader').hide();
                    _self.institutions = responses[0];
                    _self.organizations = responses[1];
                    _self.departments = responses[2];
                    _self.periods = responses[3];
                    _self.levelGrades = responses[4];
                    _self.reasons = responses[5];
                    _self.statuses = responses[6];

                    _self.levelDescs = _self.levelGrades.reduce((result, item) => {
                        if(!result.includes(item.Descr)) result.push(item.Descr);
                        return result;
                    }, []);
                    _self.isFormLoading = false;
                    $('.has-tooltip').binus_tooltip();
                });
            },
            watch : {
                candidates : function(){
                    var _self = this;
                    _self.$nextTick().then( function(){
                        new Promise(function(resolve) {
                            $('.freeze-pane').binus_freeze_pane({
                                fixed_left  : 2,
                                height      : 400,
                            });
                            resolve(true);
                        });
                    });
                }
            },
            methods : {
                isCanEditStatus : function(candidate, isPeriod=true){
                    var _self = this;
                    const rules = [
                        _self.STATUS_WAITING,
                        _self.STATUS_DECLINED_LRC,
                        _self.STATUS_ON_REVIEW,
                        _self.STATUS_REVIEWED,
                    ];
                    if(candidate.Period !== null && isPeriod) rules.push(_self.STATUS_OPEN);
                    return !rules.includes(candidate.StatusID);
                },
                disableStatus : function(candidate, isPeriod=true){
                    var _self = this;
                    return _self.isCanEditStatus(candidate, isPeriod) && _self.editForm[candidate.CandidateID];
                },
                filterPeriods : function(institutionID){
                    if(typeof institutionID ==  'undefined' || institutionID == '') 
                        return this.periods;
                    return [...this.periods].filter(item => item.Inst == institutionID);
                },
                getReasons : function(){
                    return new Promise((resolve, reject) => {
                        axios.get('general/reasons')
                        .then(response => resolve(response.data))
                        .catch(err => reject(err));
                    });
                },
                getStatuses : function(){
                    return new Promise((resolve, reject) => {
                        axios.get('general/statuses')
                        .then(response => resolve(response.data))
                        .catch(err => reject(err));
                    });
                },
                getPeriods : function(institution){
                    return new Promise(function(resolve, reject){
                        axios.get(`period/all`)
                        .then(response => {
                            resolve(response.data);
                        }).catch(err => reject(err));
                    });
                },
                getDepartments : function(acad_career){
                    return new Promise(function(resolve, reject){
                        axios.get(`general/departments`)
                        .then(response => {
                            resolve(response.data);
                        }).catch(err => reject(err));
                    });
                },
                getIntitutions : function(){
                    return new Promise(function(resolve, reject){
                        axios.get(`${baseUri.general}/institutions`)
                        .then(response => {
                            const insts = response.data.reduce((res, item) => {
                                res[item.Inst] = item;
                                return res;
                            }, {});
                            resolve(insts);
                        })
                        .catch(err => resolve(err.response.data))
                    });
                },
                getOrganizations : function(){
                    return new Promise(function(resolve, reject){
                        axios.get(`general/organizations`)
                        .then(response => {
                            resolve(response.data);
                        }).catch(err => reject(err));
                    });
                },
                getLevelDescs : function(){
                return new Promise((resolve, reject) => {
                        axios.get('general/leveldescs')
                        .then(response => resolve(response.data))
                        .catch(err => reject(err));
                    });
                },
                getCandidates : function(params = {}){
                    return axios.get('candidate/all', { params });
                },
                changeStatusAPI : function(data){
                    return axios.post('candidate/statuses', data);
                },
                changeStatus : function(candidate_id, status_id){
                    var _self = this;
                    if(!confirm('Are you sure want to change status to '+ _self.statuses[status_id - 1].Status +' ?')) return;
                    const data = {
                        candidate_id,
                        status_id
                    };
                    
                    _self.changeStatusAPI(data).then(function(response){
                        delete _self.editForm[candidate_id];
                        const candidates = [..._self.candidates].map(candidate => {
                            if(candidate.CandidateID != candidate_id) return candidate;

                            const status = [..._self.statuses].find(item => item.StatusID == status_id);
                            candidate.StatusID = parseInt(status_id);
                            candidate.Status = status.Status;
                            return candidate;
                        });
                        _self.candidates = [...candidates];
                        BM.successMessage('Status has been changed', 'success', () => {});
                    }).catch(function(err){
                        BM.successMessage(err.response.data.message, 'failed', () => {});
                    });
                },
                editNextGradeJKA : function(nextGradeJKA, candidate){
                    const temp = _self.editForm[candidate.CandidateID];
                    _self.editForm[candidate.CandidateID] = {...temp, NextGradeJKA : nextGradeJKA};
                },
                searchClicked : function(){
                    var _self = this;
                    if(_self.isSearching) return;
                    _self.editForm = {};
                    _self.isSearching = true;
                    const params = { 
                        institution : _self.form.institution.Inst || null,
                        organization : _self.form.organization.ACAD_ORG || '*',
                        department : _self.form.department.Dep || '*',
                        period_id : _self.form.period.PeriodID || null
                    };
                    _self.printData = Object.assign({}, params);
                    _self.printData.institution_name = _self.form.institution.InstName;
                    _self.printData.organization_name = _self.form.organization.DESCR || '*';
                    _self.printData.department_name = _self.form.department.DepName;
                    _self.printData.period_date = _self.form.period.Period;
                    _self.candidates = [];
                    _self.ACTION = _self.ACTION_EDIT;
                    _self.getCandidates(params)
                    .then(res => {
                        _self.candidates = res.data;
                        if([...res.data].length < 1) BM.successMessage('No data found', 'failed', () => {});
                        _self.isSearching = false;
                        _self.errors = {};
                    }).catch(err => {
                        _self.errors = err.response.data;
                        _self.isSearching = false;
                    });
                },
                selectAllCandidateClicked: function(){
                    var _self = this;                    
                    const selected = _self.candidates.reduce((res, data) => {
                        const temp = res;
                        temp[data.CandidateID] = true;
                        return temp;
                    }, {});
                    _self.editForm = {...selected };
                },
                selectedToEditFactory : function(candidate){
                    return { CandidateTrID : candidate.CandidateID, NextGradeJKA : candidate.NextGradeJKA, LecturerCode : candidate.LecturerCode, Name : candidate.Name };
                },
                selectAll : function(){
                    var _self = this;                    
                    const selected = _self.candidates.reduce((res, data) => {
                        const temp = res;
                        if(!_self.isCanEditStatus(data)) return temp;
                        temp[data.CandidateID] = _self.selectedToEditFactory(data);
                        return temp;
                    }, {});
                    _self.editForm = {...selected };
                },
                selectAllClicked : function(e){
                    var _self = this;
                    if (e.target.checked) {
                        _self.selectAll();
                    }
                    else{
                        _self.editForm = {};
                    }
                },
                onEditChecked : function(e, candidate){
                    const id = candidate.CandidateID;
                    var _self = this;
                    if (e.target.checked) {
                        _self.editForm[id] = _self.selectedToEditFactory(candidate);
                    }
                    else{
                        delete _self.editForm[id];
                    }
                    _self.editForm = Object.assign({}, _self.editForm);
                },
                onSelected : function(e,candidate){
                    const id = candidate.CandidateID;
                    var _self = this;
                    if (e.target.checked) {
                        _self.editForm[id] = true;
                    }
                    else{
                        delete _self.editForm[id];
                    }
                    _self.editForm = Object.assign({}, _self.editForm);
                },
                downloadFile : function(data, filename){
                    const url = window.URL.createObjectURL(new Blob([data]));
                    const element = document.createElement('a');
                    element.href = url;
                    element.setAttribute('download', filename);
                    document.body.appendChild(element);
                    element.click();
                    element.remove();
                },
                onPrintClicked : function(){
                    var _self = this;
                    if(_self.isPrinting) return;
                    _self.isPrinting = true;
                    axios({
                        params : _self.printData,
                        url: 'candidate/report',
                        method: 'GET',
                        responseType: 'blob', // important
                      })
                    .then(res => {
                        _self.isPrinting = false;
                        _self.downloadFile(res.data, 'ListCandidateReport.xlsx');
                    }).catch(err => {
                        _self.isPrinting = false;
                    });
                },
                onSelectedCandidateToAdd : function(e, candidate){
                    var _self = this;
                    if (e.target.checked) {
                        _self.candidatesToAdd.push({ 
                            LecturerCode : candidate.LecturerCode,
                            CurrentJKA : candidate.CurrentJKA,
                            CurrentGradeJKA : candidate.CurrentGradeJKA,
                            NextJKA : candidate.NextJKA,
                            NextGradeJKA : candidate.NextGradeJKA,
                            ReasonID : candidate.ReasonID
                        });
                    }
                    else{
                        _self.candidatesToAdd = _self.candidatesToAdd.filter(item => (
                            item.LecturerCode != candidate.LecturerCode
                        ));
                    }
                },
                onSave : function(){
                    var _self = this;
                    if(_self.isSaving) return;
                    if(_self.ACTION == _self.ACTION_ADD){
                        if(_self.candidatesToAdd.length < 1){
                            BM.successMessage('No data selected', 'failed', () => {});
                            return;
                        }
                        _self.isSaving = true;
                        const form = { 
                            period_id : _self.form.period.PeriodID,
                            institution : _self.form.institution.Inst,
                            organization : _self.form.organization.ACAD_ORG || '*',
                            department : _self.form.department.Dep || '*',
                        };
                        const data = [..._self.candidatesToAdd];
                        _self.saveAddCandidate({data, form}).then(res => {
                            _self.isSaving = false;
                            _self.candidatesToAdd = [];
                            _self.candidates = [];
                            _self.editForm = {};
                            BM.successMessage('Data has been saved', 'success', () => {});
                        })
                        .catch(err => {
                            _self.isSaving = false;
                        });
                    }
                    else if(_self.ACTION ==  _self.ACTION_EDIT){
                        if(Object.keys(_self.editForm).length < 1){
                            BM.successMessage('No data selected', 'failed', () => {});
                            return;
                        }
                        _self.isSaving = true;
                        _self.saveUpdate(Object.values(_self.editForm)).then(res => {
                            _self.isSaving = false;
                            _self.editForm = {};
                            _self.ACTION = null;
                        })
                        .catch(err => {
                            _self.isSaving = false;
                        });
                    }
                },
                isPeriodNull : function(){
                    var _self = this;
                    return typeof _self.form.period.Per != 'undefined' && _self.form.period.Per == null;
                },
                saveUpdate : function(data){
                    return axios.post('candidate/update', data);
                },
                saveAddCandidate : function(data){
                    return axios.post('candidate/add', data);
                },
                onPost : function(){
                    var _self = this;
                    if(confirm('Are you sure want to post?') == false) return;
                    if(Object.keys(_self.editForm).length < 1){
                        alert('No data selected');
                        return;
                    }
                    if(_self.isPosting) return;
                    _self.isPosting = true;
                    axios.post('candidate/post', Object.values(_self.editForm)).then(res => {
                        _self.ACTION = null;
                        _self.searchClicked();
                        _self.editForm = {};
                        _self.isPosting = false;
                        BM.successMessage('Data has been posted', 'success', () => {});
                    }).catch(err => {
                        _self.isPosting = false;
                    });
                },
                onAddClicked : function(){
                    var _self = this;
                    _self.ACTION = _self.ACTION_ADD;
                    _self.candidates = [];
                    _self.candidatesToAdd = [];
                },
                onAddCandidateClicked : function(){
                    var _self = this;
                    _self.candidates = [];
                    _self.ACTION = _self.ACTION_ADD;
                    _self.candidatesToAdd = [];
                    _self.isAddCandidateSearching = true;
                    _self.errors = {};
                    const params = { 
                        period_id : _self.form.period.PeriodID,
                        institution : _self.form.institution.Inst,
                        organization : _self.form.organization.ACAD_ORG || '*',
                        department : _self.form.department.Dep || '*',
                    };
                    axios.get('candidate/add', { params }).then(res => {
                        _self.isAddCandidateSearching = false;
                        _self.candidates = res.data;
                    }).catch(err => {
                        _self.isAddCandidateSearching = false;
                        _self.errors = err.response.data;
                    });
                },
                filterLevelGrades : function(text){
                    return this.levelGrades.filter(item => {
                        return item.Descr == text
                    });
                },
                filterStatuses(current_status, period='') {
                    var _self = this;
                    var rules = [
                        [-1],
                        [_self.STATUS_OPEN], //NULL
                        [_self.STATUS_WAITING,_self.STATUS_APPROVED_HOP,_self.STATUS_DECLINED_HOP],
                        [_self.STATUS_APPROVED_HOP,_self.STATUS_DECLINED_LRC,_self.STATUS_ON_PROCESS],
                        [_self.STATUS_DECLINED_HOP,_self.STATUS_ON_PROCESS],
                        [_self.STATUS_DECLINED_LRC],
                        [_self.STATUS_ON_PROCESS,_self.STATUS_ON_REVIEW],
                        [_self.STATUS_ON_REVIEW, _self.STATUS_ON_PROCESS, _self.STATUS_REVIEWED],
                        [_self.STATUS_REVIEWED]
                    ];
                    if(period == null) rules[1].push(_self.STATUS_ON_PROCESS);
                    return [..._self._self.statuses].filter((status) => {
                        return rules[current_status].includes(status.StatusID);
                    });
                }
            },
            computed : {
                filteredCandidates() {
                    if(this.searchText == null || this.searchText == '') return [...this.candidates];
                    let filterRgx = new RegExp(this.searchText, 'i');
                    return [...this.candidates].filter(candidate => {
                        return candidate.LecturerCode.match(filterRgx) || 
                            candidate.Name.match(filterRgx) || 
                            candidate.Dep.match(filterRgx) || 
                            candidate.DepName.match(filterRgx) || 
                            candidate.Reason.match(filterRgx);
                    });
                }
            }
        });
    }
};
Vue.directive('load', function(el,binding, vnode){
    if(typeof binding.value == 'function') binding.value(el);
}); 
