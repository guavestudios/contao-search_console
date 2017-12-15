
!function($){

    $(function(){

        $('#tl_navigation').find('ul:first').before($('<div id="search_box_container">' +
            '<form action="'+window.location.pathname+'?do=search_console&ref='+Contao.referer_id+'" method="post">' +
            '<input placeholder="search|cmd" type="text" id="search_console" name="search_console" value="" />' +
            '<input type="hidden" name="REQUEST_TOKEN" id="search_console_request_token" value="'+Contao.request_token+'" />' +
            '<span id="search_console_help">?</span>' +
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

        function split( val ) {
            return val.split( / \s*/ );
        }
        function extractLast( term ) {
            return split( term ).pop();
        }

        $("#search_console").catcomplete({
            source: function (request, response) {
                request.REQUEST_TOKEN = $('#search_console_request_token').val();
                request.action = 'search_console';
                $.post(window.location.pathname+"?do=search_console", request, function( data, status, xhr ) {

                    if(parseInt(data.resultCount) > 0) {
                        $('#main').html(data.resultHtml);
                    } else {
                        $('#main').html('nothing found');
                    }

                    response( data.items );
                });
            },
            minLength: 1,
            noCache: true,
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );

                // remove the current input
                terms.pop();

                // add the selected item
                terms.push( ui.item.value );
                // add placeholder to get the comma-and-space at the end
                terms.push( "" );
                this.value = terms.join( " " );
                this.value = this.value.substring(0, this.value.length - 1);
                return false;
            }
        });

        $('#search_console_help').on('click', function ()
            {
                alert('todo help');
            }
        );

    });


}(jQuery);