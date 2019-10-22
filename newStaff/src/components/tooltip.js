Vue.component('tooltip', {
    props : ['title'],
    template : `
        <a class="has-tooltip" :title="title" ref="tooltip">
            <slot></slot>
        </a>
    `,
    mounted : function(){
        var _self = this;
        const className = `tooltip-${Date.now()}`;
        _self.$refs.tooltip.classList.add(className);
        $(`.${className}`).binus_tooltip();
    }
});