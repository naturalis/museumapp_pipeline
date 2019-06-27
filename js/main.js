var options = {collapsed: true, withQuotes: true, rootCollapsable: false, withLinks: true};
var json;

function generatePreviewFiles()
{
    if (confirm("are you sure?"))
    {
        $('#theForm').submit();
    }
}

function publishPreviewFiles()
{
    if (confirm("are you sure?"))
    {
        $('#action').val('publish');
        $('#theForm').submit();
    }
}

function deleterPreviewFiles()
{
    if (confirm("are you sure?"))
    {
        $('#action').val('delete');
        $('#theForm').submit();
    }
}

function loadPreview(ele)
{
    var filename = $(ele).attr('data-filename');

    $('#preview .title').html(filename);
    $('#list>ul>li>span').removeClass('highlight');

    $.ajax({
        method: "POST",
        url: "ajax.php",
        data: { file: filename }
    })
    .done(function( data )
    {
        json=$.parseJSON(data);
        $('#json-renderer').jsonViewer(json, options);
        $('#preview .title,.toggle').toggle(true);
        $(ele).addClass('highlight');
    });
}
function toggleCollapse()
{
    options.collapsed = !options.collapsed;
    $('#json-renderer').jsonViewer(json, options);    
    $('#preview .toggle').html(options.collapsed ? "expand all" : "collapse");
}
