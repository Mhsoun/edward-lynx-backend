@foreach ($pages as $page)
    <div class="pageOrder" id="pageOrder_{{ $page->pageId }}" style="clear: left">
        <input type="hidden" class="pageOrderValue" name="pageOrder_{{ $page->pageId }}" value="{{ $page->order }}" autocomplete="off">
        <a class="textButton" onclick="javascript:movePage({{ $page->pageId }}, -1)">
            <span class="glyphicon glyphicon-menu-up" style="float: left; font-size: large"></span>
        </a>

        <a class="textButton" onclick="javascript:movePage({{ $page->pageId }}, 1)">
            <span class="glyphicon glyphicon-menu-down" style="float: left; font-size: large; margin-top: 12px; margin-left: -18px; margin-right: 5px"></span>
        </a>

        <span style="float: left;">{{ $page->name }}</span>
    </div>
@endforeach

<script type="text/javascript">
    //Moves the page
    function movePage(pageId, dir) {
        var pageOrderBox = $("#pageOrder_" + pageId);
        var changePlaceBox = null;

        var canMove = false;
        if (dir == -1) {
            var prev = pageOrderBox.prev('.pageOrder');
            if (prev.length > 0) {
                canMove = true;
                pageOrderBox.insertBefore(prev);
                changePlaceBox = prev;
            }
        } else if (dir == 1) {
            var next = pageOrderBox.next('.pageOrder');
            if (next.length > 0) {
                canMove = true;
                pageOrderBox.insertAfter(next);
                changePlaceBox = next;
            }
        }

        if (canMove) {
            var pageOrderValue = pageOrderBox.find(".pageOrderValue");
            pageOrderValue.val(+pageOrderValue.val() + dir);

            var changePageOrderValye = changePlaceBox.find(".pageOrderValue");
            changePageOrderValye.val(+changePageOrderValye.val() - dir);
        }
    }
</script>
