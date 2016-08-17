<div class="holidaysmode-message sf-contener clearfix col-lg-12">

	{$holidaysmode_message|escape:'UTF-8'}

	{if $holidaysmode_return_date}

		{l s='Resume working normally the' mod='holidaysmode'}&nbsp;{dateFormat date=$holidaysmode_return_date full=false}

	{/if}

</div>