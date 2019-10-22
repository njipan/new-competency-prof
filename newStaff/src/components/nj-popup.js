const njPopup = Vue.component('nj-popup', {
    template : `
        <div class="page-screen nj-popup" @click="onClick" ref="popup">
            <slot></slot>
        </div>
    `,
    methods : {
        onClick : function(e){
            if(this.$refs.popup == e.target) this.$emit('close');
        }
    },
});