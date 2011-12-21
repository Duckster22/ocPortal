{+START,IF,{$EQ,{MEMBER_ID},{$USER}}}
	{$BLOCK,block=main_activities_state,member={MEMBER_ID},mode=all,param=}

	<hr class="spaced_rule" />
{+END}

{$BLOCK,block=main_activities,member={MEMBER_ID},mode=all,param=}

{+START,IF_NON_EMPTY,{SYNDICATIONS}}
	<hr class="spaced_rule" />

	<p>{!CREATE_SYNDICATION_LINK}</p>

	<form action="{$SELF_URL*}#tab__activities" method="post">
		<div>
			{+START,LOOP,SYNDICATIONS}
				{+START,IF,{SYNDICATION_IS_SET}}
					<input class="button_pageitem" onclick="disable_button_just_clicked(this);" type="submit" name="syndicate_stop__{_loop_key*}" value="{!STOP_SYNDICATING_TO,{SYNDICATION_SERVICE_NAME*}}" />
				{+END}
				{+START,IF,{$NOT,{SYNDICATION_IS_SET}}}
					<input class="button_pageitem" onclick="disable_button_just_clicked(this);" type="submit" name="syndicate_start__{_loop_key*}" value="{!START_SYNDICATING_TO,{SYNDICATION_SERVICE_NAME*}}" />
				{+END}
			{+END}
		</div>
	</form>
{+END}