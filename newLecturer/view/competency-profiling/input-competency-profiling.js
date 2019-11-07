var sv,inputCompetencyProfilingApp;
var subView = {
    title: 'Input Competency Profiling',
    require: 'competency-profiling',
    rel: 'competency-profiling-content',
    onLoaded: function() {
        sv = this;
        $.fancybox.update();
        sv.onRequire();
        let elTitle = $('#competency-profiling-title');
        elTitle.text(elTitle.text().split("-")[0].trim() + ' - ' + sv.title.trim());
        $('#competency-profiling-loader').hide();
		document.title = 'COMPETENCY PROFILING - INPUT COMPETENCY';
		sv.prepare();
    },
    onRequire : function(){
        Promise.all([
            requireScript(BM.baseUri+`newstaff/src/components/nj-dropdown-list.js`,window['njDropdownList']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-datepicker.js`,window['njDatepicker']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-button.js`,window['njButton']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-popup.js`,window['njPopup']),
            requireScript(BM.baseUri+`newstaff/src/components/nj-freezepane.js`,window['']),
            requireScript(BM.baseUri+`newstaff/src/components/tooltip.js`,window['']),
            requireScript(BM.baseUri+`newlecturer/view/competency-profiling/components.js`,window['']),
            requireScript(BM.baseUri+`newlecturer/view/competency-profiling/components.js`,window['']),
            requireScript(BM.baseUri+`newlecturer/view/competency-profiling/src/jquery.mask.min.js`,window['']),
        ])
        .then(function(){
            sv.vue();
        });
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
    vue : function(){
        axios.defaults.baseURL = BM.serviceUri + 'competency-profiling';
        axios.defaults.headers.post['Content-Type'] = 'application/json';
        axios.interceptors.response.use(
            (response) => (response),
            (error) => {
                if(typeof error.response.data.message == 'string'){
                    BM.successMessage(error.response.data.message,'failed', () => {});
                    delete error.response.data.message;
                }
                return Promise.reject(error);
            }
        );
        const initData = {
            ACTION_TYPE : null,
            TYPE_CREATE : 'create',
            TYPE_SEARCH : 'search',
            ACADEMIC : '01-ACAD',
            TECHNICAL : '02-TECH',
            BEHAVIOUR : '03-BHVR',
            TEACHING_LEARNING : 'TEACH',
            RESEARCH : 'RSCH',
            COMMUNITY_DEVELOPMENT : 'COMDEV',
            TOEFL : 'TOEFL',
            MAX_SIZE_FILE : 20000000,
            periods : [],
            researchLevels : [],
            itemTypes : [],
            itemSubTypes : [],
            membershipStatuses : [],
            forms : [],
            dataSubTypes : [],
            form:{
                itemType : {},
                itemSubType : {},
                period: {}
            },
            fileRules : {
                teachingForm : [
                    "pdf",
                    "ppt",
                    "zip"
                ],
                additionalMaterials : [
                    "pdf",
                    "mp4",
                    "mpeg",
                    "docx",
                    "zip"
                ],
                supportingMaterials : [
                    "docx",
                    "pdf",
                    "zip",
                ],
                researchSupportingMaterials : [
                    "pdf",
                    "png",
                    "jpg",
                    "jpeg",
                    "zip",
                ],
                toefl : [
                    "pdf",
                    "png",
                    "jpg",
                    "jpeg",
                    "zip",
                ]
            },
            dataToefl : {
                isSubmitting : false,
                errors : {
                    certificate : ''
                }
            },
            errors : {},
            formTypeState : null,
            modalTypeState : null,
            searchTypeState : null,
            searchSubTypeState : null,
            isFetchingSubType : false,
            isPageLoaded : true,
            isSubmitting : false,
            regexDate : /^([0-2][0-9]|(3)[0-1])(-)(((0)[0-9])|((1)[0-2]))(-)\d{4}$/i,
            isGeneralFetching : false,
            filtered : {
                items : ['01-ACAD', '02-TECH'],
                subitems : ['COMDEV','RSCH','TEACH','TOEFL']
            },
            searchParams : {}
        };
        inputCompetencyProfilingApp = new Vue({
            el: '#input-competency-profiling-app',
            data : initData,
            methods : {
                getPeriods : function(){
                    return new Promise(function(resolve, reject){
                        axios.get(`period/candidate`)
                        .then(function(response){
                            resolve(response.data);
                        });
                    });
                },
                getItemTypes : function(){
                    return new Promise(function(resolve, reject){
                        axios.get(`general/item_types`)
                        .then(function(response){
                            resolve(response.data);
                        });
                    });
                },
                getItemSubTypes : function(){
                    return new Promise(function(resolve, reject){
                        axios.get(`general/item_subtypes`).then(function(response){
                            resolve(response.data);
                        });
                    });
                },
                getSubTypeData : function(uri,periodId){
                    return new Promise(function(resolve, reject){
                        axios.post(uri, {
                            period_id : periodId
                        }).then(function(response){
                            resolve(response.data);
                        }).catch(function(err){
                            reject(err.response.data);
                        })
                    });
                },
                getResearchLevels : function(){
                    return new Promise(function(resolve, reject){
                        axios.get('research/levels')
                        .then(function(response){
                            resolve(response.data);
                        }).catch(function(err){
                            reject(err.response.data);
                        })
                    });
                },
                getMembershipStatuses : function(){
                    return new Promise(function(resolve, reject){
                        axios.get('research/memberships')
                        .then(function(response){
                            resolve(response.data);
                        }).catch(function(err){
                            reject(err.response.data);
                        })
                    });
                },
                getSubTypes : function(N_ITEM_ID){
                    var _self = this;
                    const subtypes = _self.itemSubTypes.filter(item => (item.N_ITEM_ID == N_ITEM_ID));
                    _self.isFetchingSubType = false;
                    return subtypes;
                },
                toggleMinMax : function(e){
                    const parent = e.target.parentElement.parentElement.parentElement.nextElementSibling;
                    const classes = [...parent.classList];
                    const hideClass = 'hide';
                    
                    if(!classes.includes('hide')){
                        parent.classList.add(hideClass);
                        e.target.style.color = '#333';
                        return;
                    }
                    parent.classList.remove(hideClass);
                    e.target.style.color = '#4db0e0';
                },
                createForm: function(){
                    this.resetTypes();
                    this.errors = {};
                    const period = this.form.period;
                    const itemtype = this.form.itemType;
                    const subtype = this.form.itemSubType;
                    if(typeof period.PeriodID == 'undefined'){
                        this.errors.period = 'Please select period';
                    }
                    if(typeof itemtype.N_ITEM_ID == 'undefined'){
                        this.errors.itemType = 'Please select item';
                    }
                    if(typeof subtype.N_SUBITEM_ID == 'undefined'){
                        this.errors.itemSubType = 'Please select sub item';
                    }
                    this.ACTION_TYPE = this.TYPE_CREATE;
                    if(subtype.N_SUBITEM_ID == this.TOEFL){
                        this.modalTypeState = subtype.N_SUBITEM_ID;
                        this.formTypeState = null;
                        this.dataToefl.errors = {};
                        return;
                    }
                    this.formTypeState = subtype.N_SUBITEM_ID;
                    this.forms = [];
                    this.addForm();
                },
                validateMainForm : function(){
                    this.errors = {};
                    const period = this.form.period;
                    const type = this.form.itemType;
                    const subtype = this.form.itemSubType;
                    if(typeof period.N_SUBITEM_ID == 'undefined'){
                        this.errors.period = 'Please select period';
                    }
                    if(typeof type.N_SUBITEM_ID == 'undefined'){
                        this.errors.itemType = 'Please select item';
                    }
                    if(typeof subtype.N_SUBITEM_ID == 'undefined'){
                        this.errors.itemSubType = 'Please select sub item';
                    }
                },
                objectFactory : function(type){
                    if(this.formTypeState === this.TEACHING_LEARNING){
                        return {
                            teachingPeriod : null,
                            course : null,
                            teachingForm : null,
                            additionalMaterials : null
                        };
                    }
                    if(this.formTypeState === this.COMMUNITY_DEVELOPMENT){
                        return {
                            activity : null,
                            startDate : null,
                            endDate : null,
                            supportingMaterials : null
                        };
                    }
                    if(this.formTypeState === this.RESEARCH){
                        return {
                            year : null,
                            title : null,
                            budgetSource : null,
                            budget : null,
                            status : null,
                            researchLevel : null,
                            publisher : null,
                            publiserVolume : null,
                            publiserNumber : null,
                            publiserYear : null,
                            publiserISSNISBN : null,
                            publicationTitle : null,
                            publicationYear : null,
                            supportingMaterials : null
                        };
                    }
                },
                resetTypes : function(){
                    this.formTypeState = null;
                    this.searchTypeState = null;
                    this.searchSubTypeState = null;
                },
                addForm : function(){
                    var _self = this;
                    _self.$nextTick(function(){
                        $('.text-currency').mask('000.000.000.000.000.000', { reverse : true });
                    });
                    let newObject = { id : Date.now() , errors : _self.objectFactory(_self.formTypeState)};
                    _self.forms.push(Object.assign(newObject, _self.objectFactory(_self.formTypeState)));
                },
                deleteForm : function(item){
                    this.forms = this.forms.filter(function(current){
                        return item.id != current.id; 
                    });
                },
                refreshMask : function(){
                    $('.text-currency').mask('000-000-000-000-000-000-000', { reverse : true });
                    $('.text-currency').mask('000.000.000.000.000.000.000', { reverse : true });
                },
                isNoError : function(){
                    for(const item of this.forms){
                        console.log(item);
                        if(Object.keys(item).length < 3) return false;
                        const check = Object.values(item.errors).find(function(error){
                            return typeof error == 'undefined' || error == null || error.trim() != '';
                        });
                        return typeof check == 'undefined';
                    }
                    return true;
                },
                onCancel : function(){
                    var _self = this;
                    _self.dataSubTypes = [];
                    setTimeout(function(){
                        _self.onSubTypeClicked( _self.searchParams.sub_item_id, _self.searchParams.period_id,_self.searchParams.item_id );
                    }, 200);
                },
                onSubTypeClicked : function(type, periodId = null, itemId = null){
                    var _self = this;
                    _self.dataSubTypes = [];
                    _self.searchSubTypeState = type;
                    const uris = {};
                    uris[_self.TEACHING_LEARNING] = 'teach/candidate';
                    uris[_self.RESEARCH] = 'research/candidate';
                    uris[_self.COMMUNITY_DEVELOPMENT] = 'comdev/candidate';
                    uris[_self.TOEFL] = 'toefl/candidate';
                    const data = {
                        period_id : periodId || _self.form.period.PeriodID,
                        item_id : itemId || _self.form.itemType.N_ITEM_ID,
                        sub_item_id : type
                    };
                    _self.searchParams = { ...data };
                    axios.post(uris[type], data)
                    .then(function(response){
                        _self.dataSubTypes = response.data;
                        if(_self.dataSubTypes.length < 1){
                            BM.successMessage('No data exist', 'failed', () => {});
                            return;
                        }
                        var fixed_column = 3;
                        if(type == _self.COMMUNITY_DEVELOPMENT) fixed_column = 2;
                        _self.$nextTick(function(){
                            $('.freeze-pane').binus_freeze_pane({
                                fixed_left  : fixed_column,
                                height      : 300
                            });
                        });
                    }).catch(err => {
                        _self.errors = err.response.data;
                    });
                },
                onSearchClicked : function(){
                    this.searchValidate();
                    if(Object.keys(this.errors).length > 0) return;
                    
                    this.ACTION_TYPE = this.TYPE_SEARCH;
                    this.resetTypes();
                    this.searchTypeState = this.form.itemType.N_ITEM_ID;
                    this.searchSubTypeState = this.form.itemSubType.N_SUBITEM_ID;
                    this.onSubTypeClicked(this.searchSubTypeState);
                },
                searchValidate : function(){
                    var _self = this;
                    _self.errors = {};
                    const form = _self.form;
                    const errors = _self.errors;

                    if(Object.keys(form.period).length < 1){
                        errors.period = "Must be selected";
                    }
                    if(Object.keys(form.itemType).length < 1){
                        errors.itemType = "Must be selected";
                    }
                    if(Object.keys(form.itemSubType).length < 1){
                        errors.itemSubType = "Must be selected";
                    }
                    _self.errors = errors;
                },
                onSearchResearch : function(text, data){
                    return data.filter((item) => item.Title.includes(text));
                },
                onSearch : function(text){
                    alert(text);
                },
                checkErrForms : function(forms){
                    for(let form of forms){
                        const errs = Object.values(form.errors);
                        for(let err of errs){
                            if(err != null && err != '') return false;
                        }
                    }
                    return true;
                },
                saveForm : function(){
                    var _self = this;
                    const isErrors = _self.checkErrForms(_self.forms);
                    if(!isErrors) return;
                    
                    const config = {
                        N_ITEM_ID : this.form.itemType.N_ITEM_ID,
                        N_ITEM_DESCR : this.form.itemType.DESCR50,
                        N_SUBITEM_ID : this.form.itemSubType.N_SUBITEM_ID,
                        N_SUBITEM_DESCR : this.form.itemSubType.DESCR50,
                        period_id : this.form.period.PeriodID
                    };
                    const form = this.$refs[this.formTypeState];
                    const formData = new FormData(form);
                    formData.append('config', JSON.stringify(config));
                    _self.isSubmitting = true;
                    axios({
                        method : 'post',
                        url : _self.getUriFromSubType(_self.formTypeState),
                        data : formData,
                        config : {
                            headers : {
                                'Content-Type' : 'multipart/form-data'
                            }
                        }
                    })
                    .then(function(res){
                        BM.successMessage('Success insert new data', 'success', () => {});
                        _self.isSubmitting = false;
                        _self.forms = [];
                    })
                    .catch(error => {
                        _self.isSubmitting = false;
                        const errorMessages = error.response.data;
                        _self.forms = _self.forms.map(function(item){
                            const messages = errorMessages[item.id] || {};
                            item.errors = messages;
                            return item;
                        });
                    });                    
                },
                proxy : function(){
                    return axios.get('candidate/proxy');
                },
                getUriUpdateFromSubType : function(subtype){
                    const insertUris = {};
                    insertUris[this.TEACHING_LEARNING] = 'teach/update';
                    insertUris[this.RESEARCH] = 'research/update';
                    insertUris[this.COMMUNITY_DEVELOPMENT] = 'comdev/update';
                    insertUris[this.TOEFL] = 'toefl/update';

                    return insertUris[subtype];
                },
                getUriDeleteFromSubType : function(subtype){
                    const insertUris = {};
                    insertUris[this.TEACHING_LEARNING] = 'teach/delete';
                    insertUris[this.RESEARCH] = 'research/delete';
                    insertUris[this.COMMUNITY_DEVELOPMENT] = 'comdev/delete';
                    insertUris[this.TOEFL] = 'toefl/delete';

                    return insertUris[subtype];
                },
                getUriFromSubType : function(subtype){
                    const insertUris = {};
                    insertUris[this.TEACHING_LEARNING] = 'subtypes/teach';
                    insertUris[this.RESEARCH] = 'subtypes/research';
                    insertUris[this.COMMUNITY_DEVELOPMENT] = 'subtypes/comdev';
                    insertUris[this.TOEFL] = 'subtypes/toefl';

                    return insertUris[subtype];
                },
                resetFile : function(index, e){
                    const parent = e.target.parentElement;
                    var fileElement = filterElement(parent, 'input[type=file]')[0];
                    const fieldName = fileElement.getAttribute('data-field');
                    fileElement.value = '';
                    if(index == null) return;
                    
                    this.forms[index].errors[fieldName] = 'File must be selected';
                },
                validateSizeAndMimeType : function(files, allowedTypes=[], MAX_SIZE=this.MAX_SIZE_FILE){
                    const maxSizeInMB = MAX_SIZE / 1000000;
                    for(let file of files){
                        const ext = file.name.split(".").pop();
                        if(file.size > MAX_SIZE){
                            return `File exceeds maximum size ${maxSizeInMB}MB`;
                        }
                        if(allowedTypes.length < 1) continue;
                        if(!allowedTypes.includes(ext)){
                            return `Only accept ${allowedTypes.join(', ')} files`;
                        }
                    }
                    return null;
                },
                onDeleted : function(id, type, column){
                    var _self = this;
                    const data = { id };
                    axios.post(_self.getUriDeleteFromSubType(type), data)
                    .then(res => {
                        const filtered = _self.dataSubTypes.filter(item => item.ToeflID != id);
                        _self.dataSubTypes = [];
                        _self.dataSubTypes = filtered;
                        _self.$nextTick(function(){
                            $(`.has-tooltip`).binus_tooltip();
                        });
                        _self.$nextTick(function(){
                            $('.freeze-pane').binus_freeze_pane({
                                fixed_left  : 2,     // default 1
                                height      : 400    // default 300
                            });
                        });
                        _self.onSubTypeClicked(_self.searchSubTypeState);
                        BM.successMessage('Data has been deleted', 'success', ()=>{});
                    }).catch(function(err){
                        
                    });
                },
                onToeflDeleted : function(id){
                    var _self = this;
                    axios.delete('toefl/candidate',{
                        headers : {
                            'content-type' : 'application/json',
                        },
                        data : { id }
                    }).then(res => {
                        _self.dataSubTypes = [];
                       const filtered = _self.dataSubTypes.filter(item => item.ToeflID != id);
                       _self.dataSubTypes = [...filtered];
                    });
                },
                findMembershipStatus : function(id){
                    const membershipStatus = this.membershipStatuses.find(item => item.StatusKepID == id);
                    if(!membershipStatus) return null;
                    return membershipStatus.StatusKep;
                },
                findResearchLevel : function(id){
                    const researchLevel = this.researchLevels.find(item => item.LevelResearchID == id);
                    if(!researchLevel) return null;
                    return researchLevel.Level;
                },
                isAnyFiles : function(files, value={}){
                    if(typeof value == 'string' && value.trim() == '') return 'File must be selected';
                    else if(files.length < 1) return 'File must be selected';
                    else return null; 
                },
                filesChanged : function(e, index='', allowedTypes=[], size=this.MAX_SIZE_FILE){
                    var _self = this;
                    const files = [...e.target.files];
                    const fieldName = e.target.getAttribute('data-field');
                    let message = _self.isAnyFiles(files,e.target.value);
                    message = message || _self.validateSizeAndMimeType(files, allowedTypes);
                    _self.forms[index].errors[fieldName] = message || '';
                },
                isEmpty : function(text){
                    return typeof text !== 'undefined' && (text == null || text.trim() == '');
                },
                validateRequireText : function(text=''){
                    if(typeof text == 'undefined' || text.trim() == '') return 'Must be filled';
                    return null;
                },
                validateRequired : function(e, text, index=''){
                    let message;
                    if(this.isEmpty(text)) message = 'Must be filled';
                    
                    if(isNaN(index)) return message;
                    const fieldName = e.target.getAttribute('data-field');
                    this.forms[index].errors[fieldName] = message; 
                },
                validateDate : function(text){
                    if(typeof text != 'undefined' && !this.regexDate.test(text))
                        return 'Must be selected';

                    return "";
                },
                validateStartDate : function(startDate, endDate){
                    let messageStartDate = this.validateDate(startDate);
                    let messageEndDate = this.validateDate(endDate);
                    if(messageStartDate.trim() != ''){
                        return messageStartDate;
                    }
                    if(typeof endDate != 'undefined' && messageEndDate.trim() == ''){
                        const parseStartDate = moment(startDate,'DD-MM-YYYY');
                        const parseEndDate = moment(endDate,'DD-MM-YYYY');
                        const result = parseEndDate - parseStartDate;
                        if(parseInt(result) < 0) 
                            return 'Must be before end date';
                    }
                    return '';
                },
                validateEndDate : function(startDate, endDate){
                    let messageStartDate = this.validateDate(startDate);
                    let messageEndDate = this.validateDate(endDate);
                    if(messageEndDate.trim() != ''){
                        return messageEndDate;
                    }
                    if(typeof startDate != 'undefined' && messageStartDate.trim() == ''){
                        const parseStartDate = moment(startDate,'DD-MM-YYYY');
                        const parseEndDate = moment(endDate,'DD-MM-YYYY');
                        const result = parseEndDate - parseStartDate;
                        if(parseInt(result) > 0) 
                            return 'Must be after start date';
                    }
                    return '';
                },
                validateFileCertificate : function(file, allowedTypes=[]){
                    const ext = (file.name || '').split(".").pop();
                    if(file.size > this.MAX_SIZE_FILE){
                        return `File exceeds maximum size ${this.MAX_SIZE_FILE / 1000000}MB`;
                    }
                    if(allowedTypes.length == 0) return null;
                    else if(!allowedTypes.includes(ext)){
                        return `Only accept ${allowedTypes.join(', ')} files`;
                    }
                    return null;
                },
                fileCertificateChanged : function(e){
                    const _self = this;
                    _self.dataToefl.errors= {};
                    const file = e.target.files[0];
                    const message = _self.validateFileCertificate(file, _self.fileRules.toefl);
                    console.log(message);
                    if(message) _self.dataToefl.errors = { certificate : message };
                },
                uploadToefl : function(){
                    var _self = this;
                    if(this.dataToefl.isSubmitting || Object.keys(this.dataToefl.errors).length > 0) return;
                    this.dataToefl.isSubmitting = true;
                    const form = this.$refs[this.modalTypeState];
                    const formData = new FormData(form);
                    formData.append('period_id', _self.form.period.PeriodID);
                    for(var pair of formData.entries()) {
                        console.log(pair[0]+ ', '+ pair[1]); 
                     }
                    _self.dataToefl.isSubmitting = true;
                    axios({
                        method : 'post',
                        url : _self.getUriFromSubType(_self.modalTypeState),
                        data : formData,
                        config : {
                            headers : {
                                'Content-Type' : 'multipart/form-data'
                            }
                        }
                    })
                    .then(function(res){
                        _self.dataToefl.isSubmitting = false;
                        const data = res.data;
                        form.reset();
                        _self.dataToefl.errors = {};
                        BM.successMessage('File has been uploaded', 'success', () => {});
                    })
                    .catch(error => {
                        _self.dataToefl.isSubmitting = false;
                        _self.dataToefl.errors = error.response.data;
                    }); 
                },
                onCloseUploadToefl : function(e){
                    this.modalTypeState = null;
                }
            },
            created : async function(){
                var _self = this;
                _self.proxy().then(res => {
                    Promise.all([
                        _self.getItemTypes(), 
                        _self.getPeriods(), 
                        _self.getItemSubTypes(), 
                        _self.getResearchLevels(),
                        _self.getMembershipStatuses()
                    ]).then(function(result){
                        _self.isPageLoaded = false;
                        _self.itemTypes = [...result[0]].filter(item => _self.filtered.items.includes(item.N_ITEM_ID) );
                        _self.periods = result[1];
                        _self.itemSubTypes = [...result[2]].filter(item => _self.filtered.subitems.includes(item.N_SUBITEM_ID) );
                        _self.researchLevels = result[3];
                        _self.membershipStatuses = result[4];
                    });
                }).catch(() => {
                    _self.isPageLoaded = false;
                    BM.successMessage('You are not allowed to see this page', 'failed', () => { 
                        window.location.href = `${BM.baseUri}newlecturer`;
                    });
                }); 
            }
        });  
    }
};