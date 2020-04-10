<p>Drag rows to reorder the positions.</p>
<table class="table table-bordered display dataTable no-footer" id="positionsSortable">
    <thead>
    <tr>
        <th>#</th>
        <th>{ts}Name{/ts}</th>
        <th>{ts}Seats{/ts}</th>
        <th>{ts}Description{/ts}</th>
        {if $isElectionAdmin and (!$isElectionRunning or !$election->is_visible)}
            <th></th>
        {/if}
    </tr>
    </thead>
    <tbody>
    {foreach from =$positions key=k item=position}
        <tr class="{if $k % 2 == 0}even{else}odd{/if}" role="row">
            {assign var='k' value=$k+1}
            <td class="positionIndex">
                <span class="position-index">{$k}</span>
                <input type="hidden" value="{$position.id}" class="position-id"/>
            </td>
            <td>{$position.name}</td>
            <td>{$position.quantity}</td>
            <td>{$position.description|nl2br}</td>
            {if $isElectionAdmin and (!$isElectionRunning or !$election->is_visible)}
                <td>
                <span>
                    <a href="{crmURL p="civicrm/elections/positions/create" q="eid=`$election->id`&epid=`$position.id`"}" class="action-item crm-hover-button" title="{ts}Edit Position{/ts}">{ts}Edit{/ts}</a>
                    <a href="{crmURL p="civicrm/elections/positions/delete" q="eid=`$election->id`&epid=`$position.id`"}" class="action-item crm-hover-button" title="{ts}Delete Position{/ts}">{ts}Delete{/ts}</a>
                </span>
                </td>
            {/if}
        </tr>
    {/foreach}
    {if $positions|@count == 0}
        <tr class="even">
            <td class="center" colspan="{if $isElectionAdmin and !$isElectionRunning}5{else}4{/if}">{ts}No positions are currently available.{/ts}</td>
        </tr>
    {/if}
    </tbody>
</table>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
    {literal}
    CRM.$(function($) {
        $( "#positionsSortable tbody" ).sortable({
            stop: function( event, ui ) {
                let positionsOrder = [];
                $('#positionsSortable tbody tr').each(function(index) {
                   var positionId = $(this).find('.position-id').val();
                   positionsOrder.push({
                     'id' : positionId,
                     'order' : (index+1),
                   });
                });

                $.ajax({
                    type: 'POST',
                    url: CRM.url('civicrm/ajax/elections/positions/reorder', 'reset=1'),
                    data: {
                        'neworder' : JSON.stringify(positionsOrder),
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 1) {
                          $('#positionsSortable tbody tr').each(function(index) {
                            $(this).find('.position-index').text((index+1));
                          });
                        }
                    },
                });

            },
        });
        $( "#positionsSortable" ).disableSelection();
    });
    {/literal}
</script>