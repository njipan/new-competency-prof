
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
                            <th>ID</th>
                            <th>Teaching Period</th>
                            <th>Course</th>
                            <th>Teaching Material</th>
                            <th>Additional Material</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in items">
                            <td>{{ item.TeachingTrID }}</td>
                            <td>{{ item.TeachingPeriod }}</td>
                            <td>{{ item.Course }}</td>
                            <td>
                                <a :title="getFileName(item.TeachingMaterialLocation)" @click.prevent="download({ MaterialID : item.MaterialID, LocationFile : item.TeachingMaterialLocation })">
                                    <i class="icon icon-download"></i>
                                </a>
                            </td>
                            <td>
                                <template v-if="typeof item.AdditionalMaterials == 'undefined' || item.AdditionalMaterials.length < 1">Empty</template>
                                <template v-else>
                                    <template v-for="file in item.AdditionalMaterials">
                                        <a :title="getFileName(file.LocationFile)" @click.prevent="download(file)">
                                            <i class="icon icon-download"></i>
                                        </a>
                                        &nbsp;
                                    </template>
                                </template>
                            </td>
                            <td>
                                <span class="clickable color-blue" @click="teach = Object.assign({},item)">
                                    <i class="icon icon-edit"></i> &nbsp;
                                </span>
                                <span class="clickable color-red" @click="onDelete(item.TeachingTrID)">
                                    <i class="icon icon-trash"></i>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nj-popup v-if="teach != null" @close="teach = null;">
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
                                        <label class="side" for="">Teaching Period <span class="color-red">*</span>
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
                                        <label class="side" for="">Course <span class="color-red">*</span>
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
                                        <label class="side" for="">Teaching Form <span class="color-red">*</span> 
                                            <br>  
                                            <span class="mini-message">Form Mengajar</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <input type="file" name="teachingForm" @change="validateTeachingForm" ref="teachingForm" style="width: 200px;">
                                        <button @click.prevent="$refs.teachingForm.value = '';formUpdate.errors = Object.assign(formUpdate.errors, { teachingForm : 'Must be selected' });">
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
                                        <label class="side" for="">Additional Material <span class="color-red">*</span>
                                            <br> 
                                            <span class="mini-message">Materi Tambahan</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <list-files 
                                        @delete = "onDeleteMaterial($event)"
                                        column-id="MaterialID" add-name="additionalMaterials[]" name="additionalMaterials" :files="teach.AdditionalMaterials">
                                        </list-files>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column" style="text-align: right">
                                        <nj-button @click="doUpdate" :is-processed="formUpdate.isSubmitting" display-text="Save" animation-color="white">
                                        </nj-button>
                                        <nj-button @click="teach = null" :is-processed="false" display-text="Cancel" animation-color="white">
                                        </nj-button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
            </nj-popup>
        </div>
        `,
        props : ['items'],
        data : function(){
            return {
                teach : null,
                isShow : true,
                search : '',
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
            onSearch : function(text){
                text = text.toLowerCase();
                var _self = this;
                if(text.trim() != '') {
                    return _self.items.filter((item) => {
                        return item.TeachingPeriod.toLowerCase().includes(text) || item.Course.toLowerCase().includes(text);
                    });
                }
                return this.items;
            },
            onDeleteMaterial : function(material_id){
                if(confirm('Are you sure want to delete?')){
                    axios.post('material/delete', { material_id }).then(res => {
                        BM.successMessage('File has been deleted', 'success', () => {});
                    }).catch(err => {
    
                    });
                }
            },
            onDelete : function(id){
                if(confirm('Are you sure want to delete?') === true) this.$emit('delete', id);
            },
            doUpdate : function(){
                var _self = this;
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
                    BM.successMessage('Error occured when updating data', 'failed', () => {});
                    _self.formUpdate.errors = err.response.data
                });
            },
        }
    });
    Vue.component('list-toefl', {
        template: `
            <div>
                <div v-if="items.length < 1 && toefl == null">Please wait ... </div>
                <div class="freeze-pane" data-fixed-left="1" data-fixed-right="0" data-height="300" v-else-if="toefl == null">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Certificate</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in items" :key="item.ToeflID">
                                <td>{{ item.ToeflID }}
                                <td>
                                    <a :title="getFileName(item.LocationFile)" @click.prevent="download(item)">
                                        <i class="icon icon-download"></i>
                                    </a>
                                </td>
                                <td>
                                    <span class="clickable color-blue">
                                        <i class="icon icon-edit" @click="toefl = item;"></i> &nbsp;
                                    </span>
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
                                <div class="column one-third">
                                    <label class="side" for="">Choose File <span class="color-red">*</span>
                                        <br> 
                                        <span class="mini-message">Pilih file</span>
                                    </label>
                                </div>
                                <div class="column two-thirds" style="overflow: hidden;">
                                    <div class="row" style="margin: 0px -10px">
                                        <div class="column two-thirds" style="overflow: hidden;">
                                            <input type="file" name="certificate" @change="certificateChanged" ref="certificate">
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
                                        <nj-button @click="toefl = null;" :is-processed="false" display-text="CANCEL" animation-color="white"></nj-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </template>
            </div>
        `,
        props : ['items', 'validateCertificate'],
        data : function(){
            return {
                toefl : null,
                isShow : true,
                search : '',
                formUpdate : {
                    data : {},
                    errors : {
                        activity : null,
                        startDate : null,
                        endDate : null,
                        supportingMaterials : null,
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
                this.formUpdate.errors.certificate = this.validateCertificate(e.target.files[0]);
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
                    _self.formUpdate.errors = err.response.data
                    _self.formUpdate.isSubmitting = false;
                });
            }
        },
    });
    Vue.component('list-community-development', {
        template: `
        <div>
            <nj-popup v-if="comdev != null && isShow" @close="comdev = null;">
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
                                            <label class="side" for="">ID <span class="color-red"></span>
                                            <br> 
                                            <span class="mini-message">ID</span>
                                            </label>
                                        </div>
                                        <div class="column two-thirds">
                                            <input type="text" @input="" :disabled="true" :value="comdev.ComdevTrID">
                                            <label class="label-message color-red">&nbsp;</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group clearfix">
                                        <div class="column one-third">
                                            <label class="side" for="">Activity <span class="color-red">*</span>
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
                                            <label class="side" for="">Date Of Implementation <span class="color-red">*</span>
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
                                                Supporting Material <span class="color-red">*</span>
                                                <br> 
                                                <span class="mini-message">File Pendukung</span>
                                            </label>
                                        </div>
                                        <div class="column two-thirds">
                                            <div class="row" style="margin: 0px -10px">
                                                <list-files 
                                                @delete = "onDeleteMaterial"
                                                column-id="MaterialID" add-name="supportingMaterials[]" name="supportingMaterials" :files="comdev.SupportingMaterials">
                                                </list-files>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group clearfix">
                                        <div class="column" style="text-align: right">
                                            <nj-button @click="doUpdate" :is-processed="formUpdate.isSubmitting" display-text="Save" animation-color="white">
                                            </nj-button>
                                            <nj-button @click="comdev = null" :is-processed="false" display-text="Cancel" animation-color="white">
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
                            <th>ID</th>
                            <th>Activity Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Supporting Materials</th>
                            <th>Action</th>
                        </tr>    
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in items">
                            <td>{{ item.ComdevTrID }}</td>
                            <td>{{ item.ActivityName }}</td>
                            <td>{{ item.FormattedStartDt }}</td>
                            <td>{{ item.FormattedEndDt }}</td>
                            <td>
                                <template v-if="typeof item.SupportingMaterials == 'undefined' || item.SupportingMaterials.length < 1">Empty</template>
                                <template v-else>
                                    <template v-for="file in item.SupportingMaterials">
                                        <a @click.prevent="download(file)" :title="getFileName(file.LocationFile)"><i class="icon icon-download"></i></a>
                                        &nbsp;
                                    </template>
                                </template>
                            </td>
                            <td>
                                <span class="clickable color-blue" @click="onUpdate(item)">
                                    <i class="icon icon-edit"></i> &nbsp;
                                </span>
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
        props : ['items'],
        data : function(){
            return {
                isShow : true,
                search : '',
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
                    if(parseInt(result) < 0) message = 'Must be after start date';
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
                if(confirm('Are you sure want to delete?')){
                    axios.post('material/delete', { material_id }).then(res => {
                        BM.successMessage('File has been deleted', 'success', () => {});
                    }).catch(err => {
    
                    });
                }
            },
            doUpdate : function(){
                var _self = this;
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
            onSearch : function(text){
                text = text.toLowerCase();
                var _self = this;
                if(text.trim() != '') {
                    return _self.items.filter((item) => {
                        return item.ActivityName.toLowerCase().includes(text);
                    });
                }
                return this.items;
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
                            <th>ID</th>
                            <th>Title</th>
                            <th>Research Year</th>
                            <th>Research Level</th>
                            <th>Budget Resource</th>
                            <th>Budget</th>
                            <th>Membership Status</th>
                            <th>Publisher Name</th>
                            <th>Volume</th>
                            <th>Number</th>
                            <th>ISSN/ISBN</th>
                            <th>Journal Year (Tahun Jurnal)</th>
                            <th>Publication Title</th>
                            <th>Publication Year</th>
                            <th>Supporting Materials</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in items" :key="item.ResearchTrID">
                            <td>{{ item.ResearchTrID }}</td>
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
                                        <a @click.prevent="download(file)" :title="getFileName(file.LocationFile)"><i class="icon icon-download"></i></a>
                                        &nbsp;
                                    </template>
                                </template>
                            </td>
                            <td>
                                <span class="clickable color-blue" @click="onUpdate(item)">
                                    <i class="icon icon-edit"></i> &nbsp;
                                </span>
                                <span class="clickable color-red" @click="onDelete(item.ResearchTrID)">
                                    <i class="icon icon-trash"></i>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nj-popup v-if="research != null" @close="research = null;">
                <div class="p-wrapper shadow-md" style="background-color: white;width: 600px;">
                    <div class="p-wrapper" style="background-color: white;overflow-y: scroll;max-height: 400px;">
                        <form ref="formUpdate" @submit.prevent="">
                            <div class="row">
                                <div class="column">
                                    <h3 style="text-align: center;" class="title-popup">Update Research</h3>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column one-third">
                                        <label class="side" for="">
                                            Year <span class="color-red">*</span>
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
                                            Title <span class="color-red">*</span>
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
                                            Budget Source <span class="color-red">*</span> 
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
                                            Budget <span class="color-red">*</span> 
                                            <br> 
                                            <span class="mini-message">Total Dana</span>
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <div style="display: flex;align-items: center;">
                                            <span style="display: block;padding: 8px;border: 1px solid #c9c9c9;border-right: none;background-color:white;">Rp </span>
                                            <input type="text" name="budget" @input="validateBudget" v-model="research.Budget">
                                        </div
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
                                            Status <span class="color-red">*</span> 
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
                                            Research Level <span class="color-red">*</span>
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
                                            Publisher <span class="color-red">*</span> 
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
                                            Volume <span class="color-red">*</span> 
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
                                            Number <span class="color-red">*</span> 
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
                                            Year <span class="color-red">*</span> 
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
                                            ISSN/ISBN <span class="color-red">*</span> 
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
                                            Publication Title <span class="color-red">*</span> 
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
                                            Publication Year <span class="color-red">*</span> 
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
                                            Supporting Material <span class="color-red">*</span> 
                                            <br> 
                                            <span class="mini-message">File Pendukung</span>                                    
                                        </label>
                                    </div>
                                    <div class="column two-thirds">
                                        <list-files 
                                        @delete = "onDeleteMaterial"
                                        column-id="MaterialID" add-name="supportingMaterials[]" name="supportingMaterials" :files="research.SupportingMaterials">
                                        </list-files>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group clearfix">
                                    <div class="column" style="text-align: right">
                                        <nj-button @click="doUpdate" :is-processed="formUpdate.isSubmitting" display-text="Save" animation-color="white">
                                        </nj-button>
                                        <nj-button @click="research = null" :is-processed="false" display-text="Cancel" animation-color="white">
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
        props : ['items', 'membershipStatuses', 'researchLevels'],
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
                return `Rp ${temp}`;
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
                else if(isNaN(value))
                    message = 'Must be numeric';

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
                this.research = Object.assign({}, item);
                this.formUpdate.data.status = this.research.StatusKepID;
                this.formUpdate.data.researchLevel  = this.research.LevelResearchID;
            },
            onDeleteMaterial : function(material_id){
                if(confirm('Are you sure want to delete?')){
                    axios.post('material/delete', { material_id }).then(res => {
                        BM.successMessage('File has been deleted', 'success', () => {});
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
                    BM.successMessage('Error occured when updating data', 'failed', () => {});
                    _self.formUpdate.errors = err.response.data
                    _self.formUpdate.isSubmitting = false;
                });
            },
            onSearch : function(text){
                text = text.toLowerCase();
                var _self = this;
                if(text.trim() != '') {
                    return _self.items.filter((item) => {
                        return item.Title.toLowerCase().includes(text) || item.Level.toLowerCase().includes(text) || item.StatusKep.toLowerCase().includes(text);
                    });
                }
                return this.items;
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
            <span>Your files : </span>
            <div v-for="(file, index) in files">
                <span>{{ index + 1 }}.&nbsp;</span>
                <input type="file" :name="getName(file[columnId])" style="width: 200px;">
                <i @click="onDelete(file[columnId], $event)" class="cursor-pointer icon icon-trash"></i>    
                <i @click="$event.target.parentNode.children[0].value = '';" class="cursor-pointer icon icon-reject"></i>    
                <a @click.prevent="download(file)" :title="getFileName(file.LocationFile)">
                    <i class="icon icon-download"></i>
                </tooltip>
            </div><br>
            <div>
                <a class="color-blue" @click="isShow = true;"> Do you want to add another files? </a><br>
                <template v-if="isShow">
                    <input  style="width: 200px;" type="file" :name="addName" multiple> 
                    <i @click="$event.target.parentNode.children[1].value = '';" class="cursor-pointer icon icon-reject"></i>    
                    <br>
                    <a class="color-blue"  @click="isShow = false">I don't</a>
                </template>
            </div>
        <div>
        `,
        methods : {
            download : function(file){
                var _self = this;
                _self.download(file);
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