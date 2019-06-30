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

function deletePreviewFiles()
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

function queuePipelineSourceRefresh( source )
{
    if (confirm("are you sure?"))
    {
        $('#action').val('refresh');

        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", "source");
        hiddenField.setAttribute("value", source );
        $('#theForm').append(hiddenField);

        $('#theForm').submit();
    }

}

var queueMessage;
var prevQueuedJobs=Array();

function printPreviousQueuedJobs()
{
    for(i=0;i<prevQueuedJobs.length;i++)
    {
        $('td.refresh[data-source='+prevQueuedJobs[i]+']').html("already queued").off("click").removeClass("clickable");
    }    
}

function printQueueMessage()
{
    if(queueMessage)
    {
        $('.refresh').each(function()
        {
            if ($(this).attr('data-source')==queueMessage.source)
            {
                $(this).html(queueMessage.message).off("click").removeClass("clickable");
            }
        })
    }
}