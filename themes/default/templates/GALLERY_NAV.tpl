<div onkeypress="return null;" onclick="cancelBubbling(event);">
	{+START,IF,{SLIDESHOW}}
		<label for="slideshow_from" class="slideshow_speed">
			{!SPEED_IN_SECS}
			<input size="4" onchange="reset_slideshow_countdown();" onmousedown="stop_slideshow_timer('{!STOPPED;=}');" onkeypress="cancelBubbling(event);" type="{+START,IF,{$VALUE_OPTION,html5}}number{+END}{+START,IF,{$NOT,{$VALUE_OPTION,html5}}}text{+END}" name="slideshow_from" id="slideshow_from" value="5" />
		</label>
		<input type="hidden" id="next_slide" name="next_slide" value="{SLIDESHOW_NEXT_URL*}" />
		<input type="hidden" id="previous_slide" name="previous_slide" value="{SLIDESHOW_PREVIOUS_URL*}" />
	{+END}

	{$JAVASCRIPT_INCLUDE,javascript_galleries}
	{$JAVASCRIPT_INCLUDE,javascript_ajax}

	{+START,BOX,,,med}
		<div class="left">
			{$,Back}
			{+START,IF_NON_EMPTY,{BACK_URL}}
				<a {+START,IF,{SLIDESHOW}}onclick="return slideshow_backward();" {+END}rel="prev" accesskey="j" href="{BACK_URL*}"><img {+START,IF,{$MOBILE}}style="width: 95px" {+END}class="button_page" title="{!PREVIOUS}" alt="{!PREVIOUS}" src="{$IMG*,page/previous}" /></a>
			{+END}
			{+START,IF_EMPTY,{BACK_URL}}
				<img {+START,IF,{$MOBILE}}style="width: 95px" {+END}class="button_page" title="{!PREVIOUS}" alt="{!PREVIOUS}" src="{$IMG*,page/no_previous}" />
			{+END}
		</div>

		<div class="right">
			{$,Start slideshow}
			{+START,IF_NON_EMPTY,{SLIDESHOW_URL}}
				{+START,IF,{$NOT,{$MOBILE}}}
					{+START,IF,{$JS_ON}}{+START,IF,{$NOT,{SLIDESHOW}}}
						<a class="link_exempt" rel="nofollow" target="_blank" title="{!SLIDESHOW}: {!NEW_WINDOW}" href="{SLIDESHOW_URL*}"><img {+START,IF,{$MOBILE}}style="width: 95px" {+END}class="button_page" title="{!SLIDESHOW}" alt="{!SLIDESHOW}" src="{$IMG*,page/slideshow}" /></a>
					{+END}{+END}
				{+END}
			{+END}

			{$,Next}
			{+START,IF_NON_EMPTY,{NEXT_URL}}
				<a {+START,IF,{SLIDESHOW}}onclick="return slideshow_forward();" {+END}rel="next" accesskey="k" href="{NEXT_URL*}"><img {+START,IF,{$MOBILE}}style="width: 95px" {+END}class="button_page" title="{!NEXT}" alt="{!NEXT}" src="{$IMG*,page/next}" /></a>
			{+END}
			{+START,IF_EMPTY,{NEXT_URL}}
				<img {+START,IF,{$MOBILE}}style="width: 95px" {+END}class="button_page" title="{!NEXT}" alt="{!NEXT}" src="{$IMG*,page/no_next}" />
			{+END}
		</div>

		{$,Different positioning of slideshow button for mobiles, due to limited space}
		{+START,IF_NON_EMPTY,{SLIDESHOW_URL}}
			{+START,IF,{$MOBILE}}
				{+START,IF,{$JS_ON}}{+START,IF,{$NOT,{SLIDESHOW}}}
					<div class="right">
						<a class="link_exempt" rel="nofollow" target="_blank" title="{!SLIDESHOW}: {!NEW_WINDOW}" href="{SLIDESHOW_URL*}"><img {+START,IF,{$MOBILE}}style="width: 95px" {+END}class="button_page" title="{!SLIDESHOW}" alt="{!SLIDESHOW}" src="{$IMG*,page/slideshow}" /></a>
					</div>
				{+END}{+END}
			{+END}
		{+END}

		<div class="nav_mid">
			<script type="text/javascript">// <![CDATA[
			addEventListenerAbstract(window,'real_load',function () {
				window.slideshow_current_position={_X%}-1;
				window.slideshow_total_slides={_N%};

				{+START,IF,{SLIDESHOW}}
					initialise_slideshow();
				{+END}
			} );
			//]]></script>

			{+START,IF,{SLIDESHOW}}
				{!VIEWING_SLIDE,{X*},{N*}}

				{+START,IF_NON_EMPTY,{SLIDESHOW_NEXT_URL}}
					<span id="changer_wrap">{!CHANGING_IN,xxx}</span>
				{+END}

				{+START,IF_EMPTY,{NEXT_URL}}
					{!LAST_SLIDE}
				{+END}
			{+END}

			{+START,IF,{$NOT,{SLIDESHOW}}}
				{!VIEWING_GALLERY_ENTRY,{X*},{N*}}
			{+END}
		</div>
	{+END}
</div>
