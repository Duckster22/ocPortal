<section class="box box___catalogue_default_grid_entry_wrap"><div class="box_inner">
	<h3><span class="name">{FIELD_0}</h3>

	{+START,SET,TOOLTIP}
		<table summary="{!MAP_TABLE}" class="results_table">
			<tbody>
				{FIELDS_GRID}
			</tbody>
		</table>
	{+END}

	{+START,IF_NON_EMPTY,{FIELD_1_THUMB}}
		<div class="catalogue_entry_box_thumbnail">
			<a onmouseover="if (typeof window.activate_tooltip!='undefined') activate_tooltip(this,event,'{$GET^;*,TOOLTIP}','500px');" href="{VIEW_URL*}">{FIELD_1_THUMB}</a>
		</div>
	{+END}

	{+START,IF_EMPTY,{FIELD_1_THUMB}}
		<a title="{$STRIP_TAGS,{FIELD_0}}" href="{VIEW_URL*}">{!VIEW}</a>
	{+END}

	<div class="ratings">
		{RATING}
	</div>
</div></section>