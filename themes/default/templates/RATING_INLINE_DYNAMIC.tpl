<span class="RATING_INLINE_DYNAMIC"{$?,{$VALUE_OPTION,html5}, itemscope="itemscope" itemtype="http://schema.org/AggregateRating"}>
	{$,Show the current result (nothing shows if nobody voted yet)}
	{+START,IF,{HAS_RATINGS}}
		{+START,LOOP,ALL_RATING_CRITERIA}
			{+START,INCLUDE,RATING_DISPLAY_SHARED}{+END}
		{+END}
		&nbsp;
	{+END}

	{$SET,block_embedded_forms,1}
	{RATING_FORM}
	{$SET,block_embedded_forms,0}
</span>
