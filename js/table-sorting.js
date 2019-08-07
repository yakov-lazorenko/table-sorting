$(document).ready( function () {
    //search filter for entities list on index pages
    $('.text_search_filter button').click(function(){
    searchRedirect();
    });

    $('.text_search_filter .search-input').keyup(function(eventObject){
    if (eventObject.which == 13){
        searchRedirect();
    }
    });
});


function searchRedirect()
{
    var text = $('.search-input').val().trim();
    if (text.length < 2 && text !== ''){
        return;
    }
    var url = removeUrlParam(window.location.href, 'page');
    if (text !== ''){
        window.location = updateUrlParam(url, 'search', text);
    } else {
        window.location = removeUrlParam(url,'search');
    }
}


function updateUrlParam(url, param, paramVal)
{
    var TheAnchor = null;
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1];
    var temp = "";

    if (additionalURL) 
    {
        var tmpAnchor = additionalURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor = tmpAnchor[1];
        if(TheAnchor)
            additionalURL = TheParams;

        tempArray = additionalURL.split("&");

        for (var i=0; i<tempArray.length; i++)
        {
            if(tempArray[i].split('=')[0] != param)
            {
                newAdditionalURL += temp + tempArray[i];
                temp = "&";
            }
        }        
    }
    else
    {
        var tmpAnchor = baseURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor  = tmpAnchor[1];

        if(TheParams)
            baseURL = TheParams;
    }

    if(TheAnchor)
        paramVal += "#" + TheAnchor;

    var rows_txt = temp + "" + param + "=" + paramVal;
    return baseURL + "?" + newAdditionalURL + rows_txt;
}


function removeUrlParam(url, param)
{
    var _url = new URL(url);
    var params = new URLSearchParams(_url.search.slice(1));
    params.delete(param);
    var resultUrl = _url.protocol + '//' + _url.hostname;
    if ( _url.pathname.length > 1 ){
        resultUrl = resultUrl + _url.pathname;
    }
    if ( params.toString().length ){
        resultUrl = resultUrl + '?' + params.toString();
    }
    return resultUrl;
}


