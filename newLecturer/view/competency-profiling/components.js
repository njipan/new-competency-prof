
function downloadFile(file){
    const material_id = file.MaterialID;
    const form = document.createElement('form');
    form.setAttribute('action', `${BM.serviceUri}competency-profiling/material/download`);
    form.setAttribute('method', `post`);
    const input = document.createElement('input');
    input.setAttribute('type', 'hidden');
    input.setAttribute('name', 'id');
    input.setAttribute('value', material_id);
    document.body.appendChild(form);
    form.appendChild(input);
    form.submit();
    form.remove();
}

function componentJS(){
    if(typeof Vue === 'undefined') return;
    
    Vue.directive('load', function(el,binding, vnode){
        if(typeof binding.value == 'function') binding.value(el);
    });
    Vue.component('list-teaching-learning', {
        template: `
        <div>
            <div class="freeze-pane" data-fixed-left="1" data-fixed-right="0" data-height="300">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: center;">No</th>
                            <th style="text-align: center;">Teaching Period</th>
                            <th style="text-align: center;">Course</th>
                            <th style="text-align: center;">Teaching Material</th>
                            <th style="text-align: center;">Additional Material</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in items">
                            <td>{{ (index + 1) }}</td>
                            <td>{{ item.TeachingPeriod }}</td>
                            <td>{{ item.Course }}</td>
                            <td>
                                <a class="cursor-download" :title="getFileName(item.TeachingMaterialLocation)" @click.prevent="download({ MaterialID : item.MaterialID, LocationFile : item.TeachingMaterialLocation })">
                                    <i class="icon icon-download"></i>
                                </a>
                            </td>
                            <td>
                                <template v-if="typeof item.AdditionalMaterials == 'undefined' || item.AdditionalMaterials.length < 1">Empty</template>
                                <template v-else>
                                    <template v-for="file in item.AdditionalMaterials">
                                        <a :title="getFileName(file.LocationFile)" @click.prevent="download(file)" class="cursor-download">
                                            <i class="icon icon-download"></i>
                                        </a>
                                        &nbsp;
                                    </template>
                                </template>
                            </td>
                            <td>
                                <span class="clickable color-blue" @click="teach = Object.assign({},item)">
                                    <i class="icon icon-edit"></i> &nbsp;
                                </span>&nbsp;
                                <span class="clickable color-red" @click="onDelete(item.TeachingTrID)">
                                    <i class="icon icon-trash"></i>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nj-popup v-if="teach != null" @close="teach = null;$emit('cancel');">
                <div class="p-wrapper shadow-md" style="background-color: white;width: 600px;">
                    <div class="p-wrapper" style="background-color: white;overflow-y: auto;max-height: 400px;">
                        <form @submit.prevent="" ref="formUpdate">
                            <div class="row">
                                <div class="column">
                                    <h3 style="text-align: center;" class="title-popup">Update Teaching</h3>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">Teaching Period 
                                            <br> 
                                            <span class="mini-message">Periode Mengajar</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="teachingPeriod" @input="validateTeachingPeriod" v-model="teach.TeachingPeriod">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.teachingPeriod }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">Course 
                                            <br> 
                                            <span class="mini-message">Mata Kuliah</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="course" @input="validateCourse" v-model="teach.Course">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.course }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">Teaching Form  
                                            <br>  
                                            <span class="mini-message">Form Mengajar</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="file" name="teachingForm" @change="validateTeachingForm" ref="teachingForm" style="width: 200px;">
                                        <button @click.prevent="$refs.teachingForm.value = '';">
                                            Clear
                                        </button>
                                        <label class="label-message color-red">                                    
                                            {{ formUpdate.errors.teachingForm }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">Additional Material 
                                            <br> 
                                            <span class="mini-message">Materi Tambahan</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <list-files 
                                        @delete = "onDeleteMaterial($event)"
                                        column-id="MaterialID" add-name="additionalMaterials[]" name="additionalMaterials" :files="teach.AdditionalMaterials">
                                        </list-files>
                                        <label class="label-message color-red">                                    
                                            {{ formUpdate.errors.additionalMaterials }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column" style="text-align: right">
                                        <nj-button @click="doUpdate" :is-processed="formUpdate.isSubmitting" display-text="Save" animation-color="white">
                                        </nj-button>
                                        <nj-button @click="teach = null;$emit('cancel');" :is-processed="false" display-text="Cancel" animation-color="white">
                                        </nj-button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
            </nj-popup>
        </div>
        `,
        props : ['items', 'search'],
        data : function(){
            return {
                teach : null,
                isShow : true,
                formUpdate : {
                    data : {},
                    errors : {
                        teachingPeriod : null,
                        course : null,
                        teachingForm : null,
                        additionalMaterials : null,
                    },
                    isSubmitting : false
                },
                fileRules : {
                    teachingForm : [
                        "pdf",
                        "ppt",
                        "zip",
                    ],
                    additionalMaterials : [
                        "pdf",
                        "mp4",
                        "mpeg",
                        "docx",
                        "zip",
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
                    ]
                },
            }
        },
        methods : {
            download : function(file){
                var _self = this;
                downloadFile(file);
            },
            getFileName : function(filepath){
                try{
                    const names = filepath.split("/") || ['Empty'];
                    return names.pop();
                }catch(e){
                }
                return '';
            },
            isEmpty : function(text){
                return typeof text == 'undefined' || text.trim() == '';
            },
            validateTeachingPeriod : function(){
                var _self = this;
                delete _self.formUpdate.errors.teachingPeriod;
                if(_self.isEmpty(_self.teach.TeachingPeriod)){
                   _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { teachingPeriod : 'Must be filled' });
                }
            },
            validateCourse : function(){
                var _self = this;
                delete _self.formUpdate.errors.course;
                if(_self.isEmpty(_self.teach.Course)){
                   _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { course : 'Must be filled' });
                }
            },
            validateTeachingForm : function(){
                var _self = this;
                const files = [..._self.$refs.teachingForm.files];
                _self.formUpdate.errors.teachingForm = null;
                if(typeof files[0].name == 'undefined'){
                    _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { teachingForm : 'Must be selected' });
                }
                const names = files[0].name.split('.');
                const type = names[names.length - 1] || '';
                const rules = _self.fileRules.teachingForm;
                if(names.length <= 1 || !rules.includes(type)){
                    let message =  `Only accept ${_self.fileRules.teachingForm.join(', ')} files`;
                    _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { teachingForm : message });
                }
            },
            onDeleteMaterial : function(material_id){
                var _self = this;
                if(confirm('Are you sure want to delete?')){
                    axios.post('material/delete', { material_id, sub_item_id : _self.search.sub_item_id, subtype_id : _self.teach.TeachingTrID }).then(res => {
                        BM.successMessage('File has been deleted', 'success', () => {});
                        _self.teach.AdditionalMaterials = [..._self.teach.AdditionalMaterials].filter(file => file.MaterialID != material_id)
                    }).catch(err => {
    
                    });
                }
            },
            onDelete : function(id){
                if(confirm('Are you sure want to delete?') === true) this.$emit('delete', id);
            },
            doUpdate : function(){
                var _self = this;
                delete _self.formUpdate.errors.additionalMaterials;
                if(_self.formUpdate.isSubmitting) return;
                _self.formUpdate.isSubmitting = true;
                const formData = new FormData(this.$refs.formUpdate);
                formData.set('id', _self.teach.TeachingTrID);
                axios({
                    method : 'post',
                    url : 'teach/update',
                    data : formData,
                    config : {
                        headers : {
                            'Content-Type' : 'multipart/form-data'
                        }
                    }
                }).then((res) => {
                    _self.formUpdate.isSubmitting = false;
                    _self.$emit('update');
                    _self.teach = null;
                    BM.successMessage('Data has been updated', 'success', () => {});
                }).catch((err) => {
                    _self.formUpdate.isSubmitting = false;
                    _self.formUpdate.errors = err.response.data;
                });
            },
        }
    });
    Vue.component('list-toefl', {
        template: `
            <div>
                <div v-if="items.length < 1 && toefl == null">Please wait ... </div>
                <div v-else-if="toefl == null" class="list-toefl-wrapper">
                    <h3 style="text-align: center;">List TOEFL</h3>
                    <table style="text-align: center;max-height: 300px;overflow: auto;white-space: nowrap;display: block;">
                        <thead>
                            <tr>
                                <th style="text-align: center;">No</th>
                                <th style="text-align: center;">File Name</th>
                                <th style="text-align: center;">Certificate</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in items" :key="item.ToeflID">
                                <td>{{ (index + 1) }}</td>
                                <td>{{ item.LocationFile }}</td>
                                <td>
                                    <a :title="getFileName(item.LocationFile)" @click.prevent="download(item)" class="cursor-download">
                                        <i class="icon icon-download"></i>
                                    </a>
                                </td>
                                <td>
                                    <span class="clickable color-blue">
                                        <i class="icon icon-edit" @click="toefl = item;"></i> &nbsp;
                                    </span>
                                    &nbsp;
                                    <span class="clickable color-red" @click="onDelete(item.ToeflID)">
                                        <i class="icon icon-trash"></i>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <template v-else>
                    <form ref="formUpdate" @submit.prevent="">
                        <h3 style="text-align: center;text-transform: uppercase;">Edit TOEFL Certificarte</h3>
                        <div class="row">
                            <div>
                                <div class="column one-third">
                                    <label class="side" for="">Choose File 
                                        <br> 
                                        <span class="mini-message">Pilih file</span>
                                    </label>
                                </div>
                                <div class="column two-thirds" style="overflow: hidden;">
                                    <div class="row" style="margin: 0px -10px">
                                        <div class="column two-thirds" style="overflow: hidden;">
                                            <input type="file" name="certificate" @change="formUpdate.errors.certificate = certificateChanged($event)" ref="certificate">
                                        </div>
                                        <div class="column one-third" style="overflow: hidden;">
                                            <span class="clickable color-blue" @click.prevent="$refs.certificate.value = ''">
                                                <i class="mdi mdi-close-circle mdi-24px"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <label class="label-message color-red">                                    
                                        {{ formUpdate.errors.certificate || "&nbsp;" }}
                                    </label>
                                    <div>
                                        <nj-button @click="onUpdate" :is-processed="formUpdate.isSubmitting" display-text="SAVE" animation-color="white"></nj-button>
                                        <nj-button @click="toefl = null;$emit('cancel');" :is-processed="false" display-text="CANCEL" animation-color="white"></nj-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </template>
            </div>
        `,
        props : ['items', 'validateCertificate', 'search'],
        data : function(){
            return {
                toefl : null,
                isShow : true,
                formUpdate : {
                    data : {},
                    errors : {
                        certificate : null,
                    },
                    isSubmitting : false
                }
            }
        },
        methods : {
            download : function(file){
                var _self = this;
                downloadFile(file);
            },
            getFileName : function(filepath){
                const names = filepath.split("/") || ['Empty'];
                return names.pop();
            },
            certificateChanged : function(e){
                return this.validateFileCertificate(e.target.files[0], ['pdf', 'png', 'jpg', 'jpeg', 'zip']);
            },
            onDelete : function(id){
                if(confirm('Are you sure want to delete?') === true) 
                    this.$emit('delete', id);
            },
            onUpdate : function(){
                var _self = this;
                if(_self.formUpdate.isSubmitting)return;
                _self.formUpdate.isSubmitting = true;
                const formData = new FormData(_self.$refs.formUpdate);
                formData.set('id', _self.toefl.ToeflID);
                axios({
                    method : 'post',
                    url : 'toefl/update',
                    data : formData,
                    config : {
                        headers : {
                            'Content-Type' : 'multipart/form-data'
                        }
                    }
                }).then((res) => {
                    _self.$emit('update');
                    _self.toefl = null;
                    _self.formUpdate.isSubmitting = false;
                    BM.successMessage('Data has been update', 'success', () => {});
                }).catch((err) => {
                    BM.successMessage('Error occured when updating data', 'failed', () => {});
                    _self.formUpdate.errors = err.response.data;
                    _self.formUpdate.isSubmitting = false;
                });
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
        },
    });
    Vue.component('list-community-development', {
        template: `
        <div>
            <nj-popup v-if="comdev != null && isShow" @close="comdev = null;$emit('cancel');">
                <div class="p-wrapper shadow-md" style="background-color: white;width: 600px;">
                    <div class="p-wrapper" style="background-color: white;overflow-y: scroll;max-height: 400px;">
                        <div class="row" style="margin: 0 !important;">
                            <form class="column" ref="formUpdate" @submit.prevent="">
                                <div class="row">
                                    <div class="column">
                                        <h3 style="text-align: center;" class="title-popup">Update Community Development</h3>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group clearfix">
                                        <div class="column one-third">
                                            <label class="side" for="">Activity 
                                            <br> 
                                            <span class="mini-message">Nama Aktivitas</span>
                                            </label>
                                        </div>
                                        <div class="column two-thirds">
                                            <input v-model="comdev.ActivityName" type="text" name="activity" @input="validateActivity">
                                            <label class="label-message color-red">
                                                {{ formUpdate.errors.activity || "&nbsp;" }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group clearfix">
                                        <div class="column one-third">
                                            <label class="side" for="">Date Of Implementation 
                                                <br> 
                                                <span class="mini-message">Tanggal Pelaksanaan</span>
                                            </label>
                                        </div>
                                        <div class="column two-thirds">
                                            <div class="row" style="margin: 0 -10px !important;">
                                                <div class="column one-half">
                                                    <nj-datepicker v-model="comdev.FormattedStartDt" name="startDate" @input="validateStartDate"></nj-datepicker>
                                                    <label class="label-message color-red">
                                                        {{ formUpdate.errors.startDate || "&nbsp;" }}
                                                    </label> 
                                                </div>
                                                <div class="column one-half">
                                                    <nj-datepicker v-model="comdev.FormattedEndDt" name="endDate" @input="validateEndDate"></nj-datepicker>
                                                    <label class="label-message color-red">
                                                        {{ formUpdate.errors.endDate || "&nbsp;" }}
                                                    </label> 
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group clearfix">
                                        <div class="column one-third">
                                            <label class="side" for="">
                                                Supporting Material 
                                                <br> 
                                                <span class="mini-message">File Pendukung</span>
                                            </label>
                                        </div>
                                        <div class="column two-thirds">
                                            <div class="row" style="margin: 0px 0px">
                                                <list-files 
                                                @delete = "onDeleteMaterial"
                                                column-id="MaterialID" add-name="supportingMaterials[]" name="supportingMaterials" :files="comdev.SupportingMaterials">
                                                </list-files>
                                                <label class="label-message color-red">
                                                    {{ formUpdate.errors.supportingMaterials || "&nbsp;" }}
                                                </label> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group clearfix">
                                        <div class="column" style="text-align: right">
                                            <nj-button @click="doUpdate" :is-processed="formUpdate.isSubmitting" display-text="Save" animation-color="white">
                                            </nj-button>
                                            <nj-button @click="comdev = null;$emit('cancel');" :is-processed="false" display-text="Cancel" animation-color="white">
                                            </nj-button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </nj-popup>
            <div class="freeze-pane">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: center;">No</th>
                            <th style="text-align: center;">Activity Name</th>
                            <th style="text-align: center;">Start Date</th>
                            <th style="text-align: center;">End Date</th>
                            <th style="text-align: center;">Supporting Materials</th>
                            <th style="text-align: center;">Action</th>
                        </tr>    
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in items">
                            <td>{{ (index + 1) }}</td>
                            <td>{{ item.ActivityName }}</td>
                            <td>{{ item.FormattedStartDt }}</td>
                            <td>{{ item.FormattedEndDt }}</td>
                            <td>
                                <template v-if="typeof item.SupportingMaterials == 'undefined' || item.SupportingMaterials.length < 1">Empty</template>
                                <template v-else>
                                    <template v-for="file in item.SupportingMaterials">
                                        <a @click.prevent="download(file)" :title="getFileName(file.LocationFile)" class="cursor-download">
                                            <i class="icon icon-download"></i>
                                        </a>
                                        &nbsp;
                                    </template>
                                </template>
                            </td>
                            <td>
                                <span class="clickable color-blue" @click="onUpdate(item)">
                                    <i class="icon icon-edit"></i> &nbsp;
                                </span>&nbsp;
                                <span class="clickable color-red" @click="onDelete(item.ComdevTrID)">
                                    <i class="icon icon-trash"></i>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        `,
        props : ['items', 'search'],
        data : function(){
            return {
                isShow : true,
                comdev : null,
                formUpdate : {
                    data : {},
                    errors : {},
                    isSubmitting : false
                }
            }
        },
        methods : {
            download : function(file){
                var _self = this;
                downloadFile(file);
            },
            getFileName : function(filepath){
                const names = filepath.split("/") || ['Empty'];
                return names.pop();
            },
            isEmpty : function(text){
                return typeof text == 'undefined' || text.trim() == '';
            },
            validateActivity : function(){
                var _self = this;
                let message = null;                
                if(_self.isEmpty(_self.comdev.ActivityName)) message = 'Must be filled';
                
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, {activity : message});
            },
            validateStartDate : function(){
                var _self = this;
                let message = null;
                if(_self.isEmpty(_self.comdev.FormattedStartDt)) message = 'Must be selected';
                if(!_self.isEmpty(_self.comdev.FormattedEndDt)){
                    const parseStartDate = moment(_self.comdev.FormattedStartDt,'DD-MM-YYYY');
                    const parseEndDate = moment(_self.comdev.FormattedEndDt,'DD-MM-YYYY');
                    const result = parseEndDate - parseStartDate;
                    if(parseInt(result) < 0) message = 'Must be at least same or before end date';
                }
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, {startDate : message});
            },
            validateEndDate : function(){
                var _self = this;
                let message = null;
                if(_self.isEmpty(_self.comdev.FormattedEndDt)) message = 'Must be selected';
                if(!_self.isEmpty(_self.comdev.FormattedStartDt)){
                    const parseStartDate = moment(_self.comdev.FormattedStartDt,'DD-MM-YYYY');
                    const parseEndDate = moment(_self.comdev.FormattedEndDt,'DD-MM-YYYY');
                    const result = parseEndDate - parseStartDate;
                    if(parseInt(result) < 0) message = 'Must be at least same or after date';
                }
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, {endDate : message});
            },
            onDelete : function(id){
                if(confirm('Are you sure want to delete?') === true) this.$emit('delete', id);
            },
            onUpdate : function(comdev){
                var _self = this;
                _self.comdev = Object.assign({}, comdev);
            },
            onDeleteMaterial : function(material_id){
                var _self = this;
                if(confirm('Are you sure want to delete?')){
                    axios.post('material/delete', { material_id, sub_item_id : _self.search.sub_item_id, subtype_id : _self.comdev.ComdevTrID }).then(res => {
                        BM.successMessage('File has been deleted', 'success', () => {});
                        _self.comdev.SupportingMaterials = [..._self.comdev.SupportingMaterials].filter(file => file.MaterialID != material_id)
                    }).catch(err => {
    
                    });
                }
            },
            doUpdate : function(){
                var _self = this;
                delete _self.formUpdate.errors.supportingMaterials;
                if(_self.formUpdate.isSubmitting) return;
                _self.formUpdate.isSubmitting = true;
                const formData = new FormData(this.$refs.formUpdate);
                formData.set('id', _self.comdev.ComdevTrID);
                axios({
                    method : 'post',
                    url : 'comdev/update',
                    data : formData,
                    config : {
                        headers : {
                            'Content-Type' : 'multipart/form-data'
                        }
                    }
                }).then((res) => {
                    _self.$emit('update');
                    _self.comdev = null;
                    _self.formUpdate.isSubmitting = false;
                    BM.successMessage('Data has been updated', 'success', () => {});
                }).catch((err) => {
                    _self.formUpdate.errors = err.response.data;
                    _self.formUpdate.isSubmitting = false;
                });
            },
        },
        created : function(){
            var _self = this;
            setTimeout(() => {
                _self.isShow = true;
            }, 1000);
        }
    });
    Vue.component('list-research', {
        template: `
        <div>
            <div v-if="items.length < 1">Please wait ... </div>
            <div class="freeze-pane" data-fixed-left="1" data-fixed-right="0" data-height="300" v-else>
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: center;">No</th>
                            <th style="text-align: center;">Title</th>
                            <th style="text-align: center;">Research Year</th>
                            <th style="text-align: center;">Research Level</th>
                            <th style="text-align: center;">Budget Resource</th>
                            <th style="text-align: center;">Budget</th>
                            <th style="text-align: center;">Membership Status</th>
                            <th style="text-align: center;">Publisher Name</th>
                            <th style="text-align: center;">Volume</th>
                            <th style="text-align: center;">Number</th>
                            <th style="text-align: center;">ISSN/ISBN</th>
                            <th style="text-align: center;">Journal Year (Tahun Jurnal)</th>
                            <th style="text-align: center;">Publication Title</th>
                            <th style="text-align: center;">Publication Year</th>
                            <th style="text-align: center;">Supporting Materials</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in items" :key="item.ResearchTrID">
                            <td>{{ (index + 1) }}</td>
                            <td>{{ item.Title }}</td>
                            <td>{{ item.Year_Research }}</td>
                            <td>{{ item.Level }}</td>
                            <td>{{ item.BudgetResource }}</td>
                            <td>{{ formattedCurrency(item.Budget) }}</td>
                            <td>{{ item.StatusKep }}</td>
                            <td>{{ item.PublisherName }}</td>
                            <td>{{ item.Volume }}</td>
                            <td>{{ item.Number }}</td>
                            <td>{{ item.ISSN_ISBN }}</td>
                            <td>{{ item.Year_Journal }}</td>
                            <td>{{ item.PublicationTitle }}</td>
                            <td>{{ item.PublicationYear }}</td>
                            <td>
                                <template v-if="typeof item.SupportingMaterials == 'undefined' || item.SupportingMaterials.length < 1">Empty</template>
                                <template v-else>
                                    <template v-for="file in item.SupportingMaterials">
                                        <a @click.prevent="download(file)" :title="getFileName(file.LocationFile)" class="cursor-download">
                                            <i class="icon icon-download"></i>
                                        </a>
                                        &nbsp;
                                    </template>
                                </template>
                            </td>
                            <td>
                                <span class="clickable color-blue" @click="onUpdate(item)">
                                    <i class="icon icon-edit"></i> &nbsp;
                                </span>&nbsp;
                                <span class="clickable color-red" @click="onDelete(item.ResearchTrID)">
                                    <i class="icon icon-trash"></i>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nj-popup v-if="research != null" @close="research = null;$emit('cancel');">
                <div class="p-wrapper shadow-md" style="background-color: white;width: 600px;">
                    <div class="p-wrapper" style="background-color: white;overflow-y: scroll;max-height: 400px;">
                        <form ref="formUpdate" @submit.prevent="" @change="refreshMask">
                            <div class="row">
                                <div class="column">
                                    <h3 style="text-align: center;" class="title-popup">Update Research</h3>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">
                                            Year 
                                            <br> 
                                            <span>Tahun Penelitian</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="year" data-field="year" v-model="research.Year_Research" @input="validateYearResearch">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.year }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">
                                            Title 
                                            <br> 
                                            <span>Judul Penelitian</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="title" @input="" v-model="research.Title" @input="validateTitleResearch">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.title }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">
                                            Budget Source  
                                            <br> 
                                            <span class="mini-message">Sumber Dana</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="budgetSource" @input="validateBudgetSource" v-model="research.BudgetResource">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.budgetSource }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side">
                                            Budget  
                                            <br> 
                                            <span class="mini-message">Total Dana</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" class="text-currency" name="budget" @input="validateBudget" v-model="research.Budget">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.budget }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side">
                                            Status  
                                            <br> 
                                            <span class="mini-message">Status Kepesertaan</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <span class="custom-combobox">
                                            <select v-model="research.StatusKepID" name="status" @change="validateStatus">
                                                <option :value="null">Please select status</option>
                                                <option v-for="status in membershipStatuses" :value="status.StatusKepID" >
                                                    {{ status.StatusKep }}
                                                </option>
                                            </select>
                                            <span class="combobox-label">
                                                {{ findMembershipStatus(research.StatusKepID) || "Please select status" }}
                                            </span>
                                        </span>
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.status }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">
                                            Research Level 
                                            <br> 
                                            <span class="mini-message">Tingkat Penelitian</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <span class="custom-combobox">
                                            <select v-model="research.LevelResearchID" name="researchLevel" @change="validateLevel">
                                                <option :value="null" selected>Please select level</option>
                                                <option v-for="researchLevel in researchLevels" :value="researchLevel.LevelResearchID">
                                                    {{ researchLevel.Level }}
                                                </option>                                        
                                            </select>
                                            <span class="combobox-label">
                                                {{ findResearchLevel(research.LevelResearchID) || "Please select level" }}
                                            </span>
                                        </span>
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.researchLevel }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side">
                                            Publisher  
                                            <br> 
                                            <span class="mini-message">Penerbit</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="publisher" v-model="research.PublisherName" @input="validatePublisherName">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.publisher }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side" style="margin-left: 20px;">
                                            Volume  
                                            <br> 
                                            <span class="mini-message">Volume</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="publisherVolume" v-model="research.Volume" @input="validatePublisherVolume">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.publisherVolume }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side" style="margin-left: 20px;">
                                            Number  
                                            <br> 
                                            <span class="mini-message">Nomor</span>
                                        </label>    
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="publisherNumber" v-model="research.Number" @input="validatePublisherNumber">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.publisherNumber }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side" style="margin-left: 20px;">
                                            Year  
                                            <br> 
                                            <span class="mini-message">Tahun</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="publisherYear" v-model="research.Year_Journal" @input="validateYearJournal">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.publisherYear }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side"  style="margin-left: 20px;">
                                            ISSN/ISBN  
                                            <br> 
                                            <span class="mini-message">ISSN/ISBN</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="publisherISSNISBN" v-model="research.ISSN_ISBN" @input="validateISSNISBN">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.publisherISSNISBN }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side">
                                            Publication Title  
                                            <br> 
                                            <span class="mini-message">Judul Publikasi</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="publicationTitle" v-model="research.PublicationTitle" @input="validatePublicationTitle">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.publicationTitle }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side">
                                            Publication Year  
                                            <br> 
                                            <span class="mini-message">Tahun Publikasi</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="text" name="publicationYear" v-model="research.PublicationYear" @input="validatePublicationYear">
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.publicationYear }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label for="" class="side">
                                            Supporting Material  
                                            <br> 
                                            <span class="mini-message">File Pendukung</span>                                    
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <list-files 
                                        @delete = "onDeleteMaterial"
                                        column-id="MaterialID" add-name="supportingMaterials[]" name="supportingMaterials" :files="research.SupportingMaterials">
                                        </list-files>
                                        <label class="label-message color-red">
                                            {{ formUpdate.errors.supportingMaterials }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column" style="text-align: right">
                                        <nj-button @click="doUpdate" :is-processed="formUpdate.isSubmitting" display-text="Save" animation-color="white">
                                        </nj-button>
                                        <nj-button @click="research = null;$emit('cancel');" :is-processed="false" display-text="Cancel" animation-color="white">
                                        </nj-button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>    
            </nj-popup>
        </div>
        `,
        props : ['items', 'membershipStatuses', 'researchLevels', 'search'],
        data : function(){
            return {
                isShow : true,
                search : '',
                formUpdate : {
                    data : {},
                    isSubmitting : false,
                    errors : {}
                },
                research : null,
            }
        },
        methods : {
            formattedCurrency : function(curr){
                const number = parseInt(curr);
                const temp = number.toLocaleString().split(',').join('.');
                return `${temp}`;
            },
            download : function(file){
                var _self = this;
                downloadFile(file);
            },
            getFileName : function(filepath){
                const names = filepath.split("/") || ['Empty'];
                return names.pop();
            },
            isEmpty : function(text){
                return typeof text == 'undefined' || text.trim() == '';
            },
            validateYearResearch : function(){
                var _self = this;
                let message = null;
                let value = _self.research.Year_Research.toString();
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { year : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';
                else if(isNaN(value))
                    message = 'Must be numeric';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { year : message });
            },
            validateTitleResearch : function(){
                var _self = this;
                let message = null;
                let value = _self.research.Title.toString();
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { title : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { title : message });
            },
            validateBudgetSource : function(){
                var _self = this;
                let message = null;
                let value = _self.research.BudgetResource.toString();
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { budgetSource : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { budgetSource : message });
            },
            validateBudget : function(){
                var _self = this;
                let message = null;
                let value = _self.research.Budget.toString();
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { budget : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { budget : message });
            },
            validateStatus : function(){
                var _self = this;
                let message = null;
                let value = (_self.research.StatusKepID || '').toString();
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { status : message });
                if(_self.isEmpty(value))
                    message = 'Must be selected';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { status : message });
            },
            validateLevel : function(){
                var _self = this;
                let message = null;
                let value = (_self.research.LevelResearchID || '').toString();
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { researchLevel : message });
                if(_self.isEmpty(value))
                    message = 'Must be selected';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { researchLevel : message });
            },
            validatePublisherName : function(){
                var _self = this;
                let message = null;
                let value = _self.research.PublisherName;
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisher : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisher : message });
            },
            validatePublisherVolume : function(){
                var _self = this;
                let message = null;
                let value = _self.research.Volume;
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisherVolume : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisherVolume : message });
            },
            validatePublisherNumber : function(){
                var _self = this;
                let message = null;
                let value = (_self.research.Number || '').toString();
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisherNumber : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';
                else if(isNaN(value))
                    message = 'Must be numeric';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisherNumber : message });
            },
            validateYearJournal : function(){
                var _self = this;
                let message = null;
                let value = _self.research.Year_Journal;
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisherYear : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';
                else if(isNaN(value))
                    message = 'Must be numeric';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisherYear : message });
            },
            validateISSNISBN : function(){
                var _self = this;
                let message = null;
                let value = _self.research.ISSN_ISBN;
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisherISSNISBN : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publisherISSNISBN : message });
            },
            validatePublicationTitle : function(){
                var _self = this;
                let message = null;
                let value = _self.research.PublicationTitle;
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publicationTitle : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publicationTitle : message });
            },
            refreshMask : function(){
                $('.text-currency').mask('000-000-000-000-000-000-000', { reverse : true });
                $('.text-currency').mask('000.000.000.000.000.000.000', { reverse : true });
                $('.text-year').mask('0000');
                $('.text-number').mask('00000000');
            },
            validatePublicationYear : function(){
                var _self = this;
                let message = null;
                let value = (_self.research.PublicationYear || '').toString();
                _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publicationYear : message });
                if(_self.isEmpty(value))
                    message = 'Must be filled';
                else if(isNaN(value))
                    message = 'Must be numeric';

                if(message != null) _self.formUpdate.errors = Object.assign(_self.formUpdate.errors, { publicationYear : message });
            },
            findResearchLevel : function(id){
                var _self = this;
                const check = _self.researchLevels.find(item => (id == item.LevelResearchID));
                if(check != null) return check.Level;
                return null;
            },
            findMembershipStatus : function(id){
                var _self = this;
                const check = _self.membershipStatuses.find(item => (id == item.StatusKepID));
                if(check != null) return check.StatusKep;
                return null;
            },
            onUpdate : function(item){
                var _self = this;
                _self.$nextTick(function(){
                    _self.refreshMask();
                });

                _self.research = Object.assign({}, item);
                _self.formUpdate.data.status = _self.research.StatusKepID;
                _self.formUpdate.data.researchLevel  = _self.research.LevelResearchID;
            },
            onDeleteMaterial : function(material_id){
                var _self = this;
                console.log({..._self.search});
                if(confirm('Are you sure want to delete?')){
                    axios.post('material/delete', { material_id, sub_item_id : _self.search.sub_item_id, subtype_id : _self.research.ResearchTrID }).then(res => {
                        BM.successMessage('File has been deleted', 'success', () => {});
                        _self.research.SupportingMaterials = [..._self.research.SupportingMaterials].filter(file => file.MaterialID != material_id)
                    }).catch(err => {
    
                    });
                }
            },
            isAnyErrors : function(errors){
                for(let error of Object.values(errors)){
                    if(error != null && error != '') return true;
                }
                return false;
            },
            doUpdate : function(){
                var _self = this;
                delete _self.formUpdate.errors.supportingMaterials;
                if(_self.isAnyErrors(_self.formUpdate.errors)) return;
                if(_self.formUpdate.isSubmitting) return;
                _self.formUpdate.isSubmitting = true;
                const formData = new FormData(this.$refs.formUpdate);
                formData.set('id', _self.research.ResearchTrID);
                axios({
                    method : 'post',
                    url : 'research/update',
                    data : formData,
                    config : {
                        headers : {
                            'Content-Type' : 'multipart/form-data'
                        }
                    }
                }).then((res) => {
                    _self.$emit('update');
                    _self.research = null;
                    _self.formUpdate.isSubmitting = false;
                    BM.successMessage('Data has been updated', 'success', () => {});
                }).catch((err) => {
                    _self.formUpdate.errors = err.response.data;
                    _self.formUpdate.isSubmitting = false;
                });
            },
            onDelete : function(id){
                if(confirm('Are you sure want to delete?') === true) this.$emit('delete', id);
            },
        }
    }); 
    Vue.component('list-files', {
        data : function(){
            return {
                isShow : false,
            }
        },
        props : ['files', 'name', 'columnId', 'addName'],
        template : `
        <div>
            <div>
                <a class="color-blue cursor-pointer" @click="isShow = !isShow;" style="margin-bottom: 4px;display: block;"> 
                    <template v-if="!isShow"> Do you want to add another files? </template>
                    <template v-else> I don't want to add files </template>
                </a>
                <template v-if="isShow">
                    <input  style="width: 200px;" type="file" :name="addName" multiple>
                    <i @click="$event.target.parentNode.children[1].value = '';" class="cursor-pointer icon icon-reject"></i>
                </template>
            </div>
            <br>
            <div class="list-files">
                <span>Your files : </span><br>
                <div v-for="(file, index) in files" class="file-item">
                    <div style="width: 20px">
                        <div>
                            <i @click="onDelete(file[columnId], $event)" class="cursor-pointer icon icon-trash" style="transform: scale(.8);"></i> &nbsp;
                            <a @click.prevent="download(file)" :title="getFileName(file.LocationFile)" class="cursor-download">
                                <i class="icon icon-download" style="transform: scale(.8);"></i>
                            </a>
                        </div>
                    </div>
                    <div style="width: 100%;padding-right: 8px; box-sizing: border-box;">
                        <div>{{ file.LocationFile }}</div>
                        <div style="margin-top: 8px;">
                            <input type="file" :name="getName(file[columnId])" style="width: 200px;" />
                            <i @click="$event.target.parentNode.children[0].value = '';" class="cursor-pointer icon icon-reject" style="transform: scale(.8);"></i>
                        </div>
                    </div>
                </div>
            <div>
        </div>
        `,
        methods : {
            download : function(file){
                var _self = this;
                downloadFile(file);
            },
            getFileName : function(filepath){
                const names = filepath.split("/") || ['Empty'];
                return names.pop();
            },
            getName : function(id){
                return `${this.name}_${id}`;
            },
            onDelete : function(id, e){
                this.$emit('delete', id, e);
            }
        }
    });
}
componentJS();