<script>
    {if isset($tracksmart_container)}
        {if isset($tracksmart_user)}
            let trackSmart = new TrackSmart('{$tracksmart_container}', {$tracksmart_user});
        {else}
            let trackSmart = new TrackSmart('{$tracksmart_container}');
        {/if}

        trackSmart.build();

        {if isset($tracksmart_event)}
            {if isset($tracksmart_data)}
                trackSmart.process('{$tracksmart_event}', {$tracksmart_data|@json_encode nofilter});
            {else}
                trackSmart.process('{$tracksmart_event}');
            {/if}
        {/if}
    {/if}
</script>
