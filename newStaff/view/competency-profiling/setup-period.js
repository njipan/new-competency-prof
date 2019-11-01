var sv,setupPeriodApp;
var subView = {
    title: 'Setup Period',
    require: 'competency-profiling',
    rel: 'competency-profiling-content',
    onLoaded: function() {
        sv = this;
        sv.onRequire();
        sv.prepare();
    },
    prepare: function(){
        window.document.title = this.title;
        $(".page-heading h1").text(this.title);
        const $breadCrumbs = $("#BC_Caption");
        while ($breadCrumbs.children().length > 1)
          $breadCrumbs
            .children()
            .last()
            .remove();
        $breadCrumbs.append('<li><a href="#">Competency Profiling</a></li>');
        $breadCrumbs.append("<li>" + this.title + "</li>");
    },
    onRequire : function(){
        Promise.all([
            requireScript(BM.baseUri+`newstaff/src/components/nj-dropdown-list.js`,window['njDropdownList']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-datepicker.js`,window['X']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-button.js`,window['X']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-popup.js`,window['njPopup']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-freezepane.js`,window['freezepane']),
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
                if(typeof error.response.data.message == 'string'){
                    BM.successMessage(error.response.data.message,'failed', () => {
                        delete error.response.data.message;
                    });
                }
                return Promise.reject(error);
            }
        );
        const initData = {
            ACTION : null,
            ACTION_EDIT : 'edit',
            ACTION_CREATE : 'create',
            form : {
                period_id : null,
                institution : {},
                startDate : null,
                effdate : {},
                endDate : null
            },
            searchText : null,
            isSending : false,
            isSearching : false,
            isPaginating : false,
            isEdit : false,
            errors : {
                search : {},
                form: {},
            },
            institutions : [],
            periods : [],
            effdates : [],
            message : null,
            baseUri : {
                general : `${BM.serviceUri}competency-profiling/General`,
                period : `${BM.serviceUri}competency-profiling/Period`
            }
        }
        setupPeriodApp = new Vue({
            el: '#setup-period-app',
            data: initData,
            created : function(){
                var _self = this;
                axios.post('staff/proxy_lrc').then(() => {
                    Promise.all([
                        this.getInstitutions(),
                        this.getEffDates(),    
                    ]).then((responses) => {
                        _self.institutions = responses[0].data;
                        _self.effdates = responses[1].data;
                        $('#competency-profiling-loader').hide();
                    }).catch(() => {
                        $('#competency-profiling-loader').hide();
                    });
                }).catch(() => {
                    BM.successMessage('You are not allowed to see this page', 'failed', () => {
                        window.location.href = `${BM.baseUri}newstaff`;
                    });
                });                
            },
            methods : {
                isEmpty : function(text){
                    return typeof text != 'string' || text.trim() == '';
                },
                onFormInstitutionChanged : function(e){
                    var _self = this;
                    let message = 'Must be chosen';
                    delete _self.errors.form['institution'];
                    if(this.isEmpty(_self.form.institution.Inst)){
                        _self.errors.form = Object.assign(_self.errors.form, { institution :  message });
                        return;
                    }
                },
                dateChanged : function(e){
                    var _self = this;
                    let errors = {};
                    const startDate = moment(_self.form.startDate, "DD-MM-YYYY");
                    const endDate = moment(_self.form.endDate, "DD-MM-YYYY");
                    const isStartDateValid = startDate.isValid();
                    const isEndDateValid = endDate.isValid();
                    delete _self.errors.form['startDate'];
                    delete _self.errors.form['endDate'];
                    
                    if( _self.isEmpty(_self.form.startDate ) ){
                        errors.startDate = 'Must be selected';
                    }
                    else if( !isStartDateValid ){
                        errors.startDate = 'Must be selected';   
                    }
                    
                    if( _self.isEmpty(_self.form.endDate )){
                        errors.endDate = 'Must be selected';
                    }
                    else if( !isEndDateValid ){
                        errors.endDate = 'Date is invalid';
                    }
                    _self.errors.form = Object.assign(_self.errors.form, errors);
                    if( _self.isEmpty(_self.form.startDate) || _self.isEmpty(_self.form.endDate) ) return;
                    const days = endDate.diff(startDate, 'days');
                    if(days < 1){
                        errors = {
                            startDate : 'Date must be before end date',
                            endDate : 'Date must be after start date'
                        };
                        _self.errors.form = Object.assign(_self.errors.form, errors);
                    }
                },
                onFormEndDateChanged : function(e){
                    var _self = this;
                    let message = 'Must be selected';
                    delete _self.errors.form['endDate'];
                    if(this.isEmpty(_self.form.endDate)){
                        _self.errors.form = Object.assign(_self.errors.form, { endDate :  message });
                        return;
                    }
                },
                onFormEffDateChanged : function(e){
                    var _self = this;
                    let message = 'Must be selected';
                    delete _self.errors.form['effdate'];
                    if(this.isEmpty(_self.form.effdate.Effdt)){
                        _self.errors.form = Object.assign(_self.errors.form, { effdate :  message });
                        return;
                    }
                },
                onCreateClicked : function(){
                    var _self = this;
                    _self.ACTION = _self.ACTION_CREATE;
                    _self.errors.form = {};
                },
                resetForm : function(){
                    this.$refs.form.reset();
                },
                getEffDates : function(){
                    return axios.get(`period/effdates`);
                },
                getInstitutions : function(){
                    return axios.get(`general/institutions`);
                },
                getPeriods : function(params){
                    return axios.get(`general/periods`, { params });
                },
                prepareSearch : function(){
                    return {
                        inst : this.form.institution.Inst,
                        start_date : this.form.startDate,
                        end_date : this.form.endDate,
                    };
                },
                validate : function(){
                    const errs = {};
                    let sDate = moment(this.form.startDate,'DD-MM-YYYY',true);
                    let eDate = moment(this.form.endDate,'DD-MM-YYYY',true);
                    if(typeof this.form.institution == 'undefined' || Object.keys(this.form.institution) < 1){
                        errs.institution = 'Please choose one institution';
                    }
                    if(!sDate.isValid()){
                        errs.startDate = 'Invalid date format';
                    }
                    if(!eDate.isValid()){
                        errs.endDate = 'Invalid date format';
                    }
                    if(!sDate.isValid() || !eDate.isValid()) return errs;
                    if(moment.duration(sDate.diff(eDate)) >= 0){
                        errs.endDate = 'End date should be after start date';
                    }
                    return errs;
                },
                search: function(){
                    var _self = this;
                    _self.periods = [];
                    _self.errors.search = {};
                    if(_self.isSearching || Object.keys(_self.errors.form).length > 0) return;
                    _self.isSearching = true;
                    _self.getPeriods(_self.prepareSearch())
                        .then(response => {
                            _self.isSearching = false;
                            _self.periods = [];
                            const oldData = [..._self.periods];
                            const payload = [...response.data];
                            if(Array.isArray(payload) && payload.length > 0) _self.periods = payload;
                            else{
                                BM.successMessage('Data not found', 'failed', () => {});
                                return;
                            }
                        })
                        .catch(err => {
                            _self.isSearching = false;
                            _self.errors.form = err.response.data;
                            BM.successMessage('Please complete all fields', 'failed', ()=> {});
                        });
                },
                update : function(){
                    var _self = this;
                    if(_self.isSending) return;
                    _self.isSending = true;
                    const data = {
                        start_date : moment(_self.form.startDate,'DD-MM-YYYY').format('YYYY-MM-DD'),
                        end_date : moment(_self.form.endDate,'DD-MM-YYYY').format('YYYY-MM-DD'),
                        institution : _self.form.institution.Inst,
                        period_id : _self.form.period_id
                    };
                    axios.post(`period/update`, data)
                    .then(function() {
                        _self.isSending = false;
                        _self.isEdit = false;
                        _self.form = {
                            institution : {},
                            startDate : '',
                            endDate : ''
                        };
                        BM.successMessage('Data has been updated', 'sucess', ()=> {});
                    })
                    .catch(err => {
                        _self.isSending = false;
                        _self.erorrs.form = err.response.data;
                    });
                },
                onDelete : function(period_id){
                    var _self = this;
                    const isDeleted = confirm('Are you sure want to delete?');
                    const data = { id : period_id };
                    if(isDeleted){
                        axios.post(`period/delete`,  data)
                        .then(() => {
                            var oldData = [..._self.periods];
                            _self.periods = [];
                            var payload = oldData.filter(item => item.PeriodID != period_id);
                            setTimeout(function(){
                                _self.periods = [...payload];
                                BM.successMessage('Data has been deleted', 'success', () => {});
                            }, 200);
                        })
                        .catch(() => {
                            
                        });
                    }
                },
                show : function(period_id){
                    var _self = this;
                    _self.isSending = true;
                    _self.periods = [];
                    const params = { id : period_id };
                    axios.get(`period/get`, { params })
                    .then((response) => {
                        _self.ACTION = _self.ACTION_EDIT;
                        _self.isEdit = true;
                        _self.isSending = false;
                        _self.form = {
                            period_id,
                            institution : {
                                Inst : response.data.InstitutionID,
                                InstName : response.data.Inst,
                            },
                            startDate : response.data.Period,
                            endDate : response.data.PeriodEnd,
                            effdate: _self.effdates.find((item) => item.Effdt == response.data.EffDt) || {},
                        };
                        _self.errors.form = {};
                    }).catch(() => {
                        _self.ACTION = null;
                        _self.isEdit = false;
                        _self.isSending = false;
                    });
                },
                cancel : function(){
                    var _self = this;
                    _self.ACTION = null; 
                    _self.errors.form = {};
                    _self.resetFormData();
                },
                isNoErrors : function(){
                    var _self = this;
                    for(let error of Object.values({..._self.errors.form})){
                        if(typeof error == 'string' && error.length > 0) return false;
                    }
                    return true;
                },
                submit: function(e){
                    var _self = this;
                    if(_self.isSending || !_self.isNoErrors()) return;

                    _self.isSending = true;
                    _self.errors.form = {};
                    if(_self.ACTION == _self.ACTION_CREATE){
                        axios.post('general/periods', _self.prepareSubmit())
                        .then(response => {
                            _self.isSending = false;
                            _self.resetFormData();
                            _self.ACTION = null;
                            BM.successMessage('Data has been inserted', 'success', () => {});
                        }).catch((err) => {
                            _self.isSending = false;
                            _self.errors.form = err.response.data;
                        });
                    }
                    else if(_self.ACTION == _self.ACTION_EDIT){
                        const data = _self.prepareSubmit();
                        data.period_id = _self.form.period_id;
                        axios.post('period/update', data)
                        .then(response => {
                            _self.isSending = false;
                            _self.resetFormData();
                            _self.ACTION = null;
                            BM.successMessage('Data has been updated', 'success', () => {});
                        }).catch((err) => {
                            _self.isSending = false;
                            _self.errors.form = err.response.data;
                        });
                    }
                },
                prepareSubmit : function(){
                    var _self = this;
                    return {
                        institution : _self.form.institution.Inst,
                        start_date : _self.form.startDate,
                        end_date : _self.form.endDate,
                        eff_date : _self.form.effdate.ConvEffdt,
                    }
                },
                resetFormData : function(){
                    var _self = this;
                    _self.resetForm();
                    _self.form = {
                        institution : {},
                        startDate : '',
                        endDate : '',
                        effdate: {},
                    };
                    _self.errors.form = {};
                }
            },
            watch : {
                periods : function(){
                    var _self = this;
                    _self.$nextTick(function(){
                        $('.freeze-pane').binus_freeze_pane({
                            fixed_left  : 2,
                            height      : 400
                        });
                    });
                }
            },
            computed : {
                filteredPeriods() {
                    if(this.searchText == null || this.searchText == '') return [...this.periods];

                    let filterRgx = new RegExp(this.searchText, 'i');
                    return [...this.periods].filter(period => {
                        return period.Inst.match(filterRgx) || 
                            period.Period.match(filterRgx) || 
                            period.PeriodEnd.match(filterRgx);
                    });
                },
                filteredEffdates() {
                    var _self = this;
                    if(_self.form.institution == null || typeof _self.form.institution.InstName == 'undefined' || _self.form.institution.InstName == '') 
                        return [];

                    return [..._self.effdates].filter(date => {
                        return date.Inst == _self.form.institution.Inst;
                    });
                }
            }
        });
    }
};