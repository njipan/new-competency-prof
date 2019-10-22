var njFreezepane = Vue.component('njFreezepane', {
    data : function (){
        return {
            stickLength : 0,
            posX : 0,
            dragX : 0,
            isDragging : false
        };
    },
    props : ['height',],
    template : `
    <div class="freeze-table-wrapper" ref="parentWrapper">
        <div class="nj-freeze-pane" ref="freezeTable">
            <table class="left-column" ref="fixLeft">
                <slot name="fixedLeft"></slot> 
            </table>
            <table class="dynamic-column" ref="dynamicTable">
                <slot name="dynamic"></slot> 
            </table>
        </div>
        <div class="nj-scrollbar" ref="scrollbar"><span class="stick" @mousedown.prevent="stickMoveStarted" @mousemove="stickMoved" @mouseout="isDragging = false;" @mouseup="stickMoveEnded" ref="stick"></span></div>
        <br>
        <div class="nj-pagination"><slot name="pagination"></slot></div>
    </div>
    `,
    methods : {
        getDynamicCurrentSpace : function(){
            var _self = this;
            return _self.$refs.parentWrapper.offsetWidth - _self.$refs.fixLeft.offsetWidth;
        },
        getDynamicFreeSpace : function(){
            var _self = this;
            return _self.$refs.dynamicTable.offsetWidth - _self.getDynamicCurrentSpace();
        },
        stickMoveStarted : function(e){
            var _self = this;
            _self.isDragging = true;
            _self.dragX = e.clientX - _self.posX;
        },
        slideDynamicTable : function(clientX, length){
            var _self = this;
            const parentWidth = _self.$refs.scrollbar.offsetWidth;
            _self.posX += (clientX - _self.posX) - length;
            if(_self.posX < _self.startX) _self.posX = _self.startX;
            else if(_self.posX + _self.stickLength > parentWidth) _self.posX = parentWidth - _self.stickLength;
            _self.$refs.stick.style.marginLeft = `${_self.posX}px`;

            const slideDynamicTableLength = _self.getDynamicFreeSpace() / (parentWidth - _self.stickLength);
            _self.$refs.dynamicTable.style.right = `${(_self.posX - _self.startX) * slideDynamicTableLength}px`
        },
        stickMoved : function(e){
            var _self = this;
            if(!_self.isDragging) return;
            _self.slideDynamicTable(e.clientX, _self.dragX);
        },
        stickMoveEnded : function(e){
            var _self = this;
            _self.isDragging = false;
        },
        totalLength : function(){
            var _self = this;
            return _self.$refs.fixLeft.offsetWidth + _self.$refs.dynamicTable.offsetWidth;
        }
    },
    mounted : function(){
        var _self = this;
        if(typeof _self.$refs.freezeTable != 'undefined') _self.$refs.freezeTable.style.height = `${_self.height || 200}px`;
        if(_self.$refs.scrollbar.offsetWidth >= _self.totalLength()) _self.$refs.dynamicTable.style.width = '100%';
        _self.stickLength = _self.getDynamicCurrentSpace() / _self.$refs.dynamicTable.offsetWidth * _self.$refs.scrollbar.offsetWidth;
        _self.$refs.stick.style.width = `${_self.stickLength}px`;
        _self.startX = _self.$refs.stick.offsetLeft
        _self.posX = _self.startX;
    },
});