var njDatepicker = Vue.component('nj-datepicker', {
    data : function(){
        var _self = this;
        return {
            value : _self.value || '',
        }
    },
    props : ['name','fieldName', 'value'],
    template : `
        <span class="custom-datepicker" ref="spanDatepicker">
            <input type="text" class="datepicker" :name="name" @blur="onBlur" :data-field="fieldName" ref="datepicker">
            <span class="icon-area"></span>
        </span>
    `,
    methods : {
        onBlur : function(){
            var _self = this;
            _self.value = _self.$refs.datepicker.value;
        }
    },
    mounted : async function(){
        var _self = this;
        let className = `nj-datepicker-${Date.now()}`;
        while(document.getElementsByClassName(className).length > 0){
            className = `nj-datepicker-${Date.now()}`;
        }
        _self.$refs.spanDatepicker.classList.add(className);
        $(`.${className}`).binus_datepicker({
            dateFormat:'dd-mm-yy',
            autoclose: true,
            changeYear  : true,
            changeMonth : true,
            onSelect:function(dateText){
                _self.value = dateText;
            }
        });
        console.log('XXXXXX');
        _self.$refs.datepicker.value = _self.value;
    },
    watch: { 
        value: function(newVal) {
            var _self = this;
            _self.$refs.datepicker.value = newVal;
            _self.$emit('input', newVal);   
        }
    }
});