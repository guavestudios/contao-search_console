
!function($){

    $(function(){
        console.info('load search-console');
        $('#tl_navigation').find('h1').append($('<div id="search_box_container">' +
            '<form action="'+window.location.pathname+'?do=search_console&ref='+Contao.referer_id+'" method="post">' +
            '<input placeholder="search|cmd" type="text" id="search_console" name="search_console" value="" />' +
            '<input type="hidden" name="REQUEST_TOKEN" id="search_console_request_token" value="'+Contao.request_token+'" />' +
            '</form>' +
            '</div>'));

        var activeFocus = $( document.activeElement );
        if(!activeFocus || activeFocus.get(0).id == 'top') {
            $('#search_console').focus();
        }

        $.widget( "custom.catcomplete", $.ui.autocomplete, {
            _create: function() {
                this._super();
                this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
            },
            _renderMenu: function( ul, items ) {
                console.info('render');
                var that = this,
                    currentCategory = "";
                $.each( items, function( index, item ) {
                    var li;
                    if ( item.category != currentCategory ) {
                        ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
                        currentCategory = item.category;
                    }
                    li = that._renderItemData( ul, item );
                    if ( item.category ) {
                        li.attr( "aria-label", item.category + " : " + item.label );
                    }
                });
            }
        });

        $("#search_console").catcomplete({
            source: function (request, response) {
                request.REQUEST_TOKEN = $('#search_console_request_token').val();
                request.action = 'search_console';
                $.post(window.location.pathname+"?do=search_console", request, function( data, status, xhr ) {

                    console.info(data);
                    if(parseInt(data.resultCount) > 0) {
                        $('#main').html(data.resultHtml);
                    } else {
                        $('#main').html('nothing found');
                    }

                    response( data.items );
                });
            },
            minLength: 0,
            noCache: true,
            select: function( event, ui ) {

                if(ui.item.action) {
                    if(ui.item.action == 'redirect') {
                        self.location.href = ui.item.url;
                    }
                }

                console.info( "Selected: " + ui.item.value + " aka " + ui.item.id + ui.item.action);
            }
        });

    });


}(jQuery);