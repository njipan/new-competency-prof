var njDropdownList = Vue.component('nj-dropdown-list', {
    data : function(){
        return {
            search : this.default || '',
            tempData : [],
        }
    },
    props: ['items','text', 'onFilter','default'],
    methods : {
        filterByConditions : function(value, conditions){
            if(conditions.length < 1) return [];
            const temp = this.items.filter(function(item){
                for(let text in conditions){
                    const textFilter = conditions[text];
                    if(typeof item[textFilter] !== 'undefined' && item[textFilter].includes(value)){
                        return true;
                    }
                }
                return false;
            });
            return temp;
        },
        filterData : function(textValue){
            if(textValue.trim() == '') {
                this.tempData = [];
                return;
            }
            if(typeof this.onFilter === 'undefined'){
                throw 'text-onFilter is not valid';
            }
            if(typeof this.onFilter == 'function'){
                this.tempData = this.items.filter(this.onFilter);
                return;
            }

            var conditions = [];
            if(typeof this.onFilter === 'string') conditions = this.onFilter.split(',');
            else if(Array.isArray(this.onFilter)) conditions = this.onFilter;
            this.tempData = this.filterByConditions(textValue, conditions);
        },
        displayText : function(item){
            return typeof this.text === 'string' ? item[this.text] : typeof this.text === 'function' ? this.text(item) : '';
        },
        onClick : function(item){
            this.$emit('change', item);
            this.$emit('input', item);
            this.tempData = [];
            this.search = this.displayText(item);
        }
    },
    template: `
        <div class="nj-dropdown-list">
          <input type="text" v-model="search" v-on:input="filterData(search)" v-bind:class="{ any : tempData.length > 0 }">
          <div class="dropdown" v-if="tempData.length > 0" v-bind:class="{ any : tempData.length > 0 }">   
            <div class="item" v-for="item in tempData" @click="onClick(item)">{{ displayText(item) }}</div>
          </div>
        </div>
    `
});