var njButton = Vue.component('nj-button', {
    data : function(){
        return {
            styles : {},
            isProcessed : this.isProcessed || false,
        }
    },
    props : ['displayText', 'isProcessed', 'animationColor'],
    methods : {
        onClick : function(e){
            this.$emit('click', e);
        },
        setAnimationColor : function(color){
            if(typeof color == 'string' || trim(color) != ''){
                this.styles.backgroundColor = color;
            }
        }
    },
    template : `
        <button @click="onClick" class="button button-primary">
            <template v-if="isProcessed">
                <div class="nj-loading s" ref="animation">
                    <div class="item" :style="styles"></div>
                    <div class="item" :style="styles"></div>
                    <div class="item" :style="styles"></div>
                </div>
            </template>
            <template v-else>{{ displayText }}</template>
        </button>
    `,
    watch : {
        animationColor : function(newVal){
            this.setAnimationColor(newVal);
        }
    },
    created : function(){
        this.setAnimationColor(this.animationColor);
    }
});