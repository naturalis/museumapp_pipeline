var options = {collapsed: true, withQuotes: true, rootCollapsable: false, withLinks: true};
var json;
var queueMessage;
var queuedJobs=Array();
var prevQueuedJobs=Array();
var harvestNumbers=Array();

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

function printQueuedJobs()
{
    $('td.refresh-trigger').toggle(true);
    $('td.refresh-state').toggle(false);

    for(i=0;i<queuedJobs.length;i++)
    {
        $('td.refresh-state[data-source='+queuedJobs[i]['source']+']').html(queuedJobs[i]['action']).toggle(true);
        $('td.refresh-state').attr('data-job',queuedJobs[i]['job']);
        $('td.refresh-trigger[data-source='+queuedJobs[i]['source']+']').toggle(false);
    }

    // console.dir(queuedJobs);
}

function printQueueMessage()
{
    if(queueMessage)
    {
        $('.refresh').each(function()
        {
            if ($(this).attr('data-source')==queueMessage.source)
            {
                $(this).html(queueMessage.message).off("click").removeClass("clickable").addClass("queued");
            }
        })
    }
}

function printHarvestNumbers()
{
    for(i in harvestNumbers)
    {
        $(".numbers[data-source="+i+"]").html(harvestNumbers[i].count);
        $(".harvest_date[data-source="+i+"]").html(harvestNumbers[i].date);
    }
}

function getHarvestNumbers()
{
    $.ajax({
        method: "GET",
        url: "_get_numbers.php"
    })
    .done(function( data )
    {
        harvestNumbers = $.parseJSON(data);
        // console.dir(harvestNumbers);
        printHarvestNumbers();
    });
}

function runQueueMonitor()
{
    $.ajax({
        method: "GET",
        url: "_get_queue.php"
    })
    .done(function( data )
    {
        prevQueuedJobs = queuedJobs.slice();
        queuedJobs = $.parseJSON(data);

        $('#generate_button').attr("disabled",(queuedJobs.length>0));

        printQueuedJobs();

        if (JSON.stringify(prevQueuedJobs) != JSON.stringify(queuedJobs))
        {
            getHarvestNumbers();
        }
    });
}

function unqueuePipelineSourceRefresh(ele)
{

    var source = ele.attr('data-source');

    if (confirm("cancel "+source+" refresh?"))
    {
        $('#action').val('cancel-refresh');
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", "source");
        hiddenField.setAttribute("value", ele.attr('data-source') );
        $('#theForm').append(hiddenField);
        var hiddenField2 = document.createElement("input");
        hiddenField2.setAttribute("type", "hidden");
        hiddenField2.setAttribute("name", "job");
        hiddenField2.setAttribute("value", ele.attr('data-job') );
        $('#theForm').append(hiddenField2);
        $('#theForm').submit();
    }
}



