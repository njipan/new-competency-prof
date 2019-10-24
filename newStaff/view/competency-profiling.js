var sv, objectToString;
var subView = {
    rel: 'content',
    title: 'Competency Profiling',
    onLoaded : function(){
        sv = this;
        sv.prepare();
    },
    prepare: function() {
        $(".page-heading h1").text(this.title);
        var $breadCrumbs = $("#BC_Caption");
        while ($breadCrumbs.children().length > 1)
          $breadCrumbs
            .children()
            .last()
            .remove();
        $breadCrumbs.append('<li><a href="#">Competency Profiling</a></li>');
        $breadCrumbs.append("<li>" + this.title + "</li>");
    },
};
objectToString = function(data={}){
    let result = '';
    let i = 0;
    for(let key in data){
        i++;
        result += `${key}=${data[key]}`;
        if(Object.keys(data).length != i){
            result += "&";
        }
    }
    return result.trim();
}
