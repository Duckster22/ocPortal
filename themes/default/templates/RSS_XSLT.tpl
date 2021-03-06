<?xml version="1.0" encoding="{!charset}"?>
<xsl:stylesheet xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="html" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"/>
	<xsl:template match="/">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$LANG*}" lang="{$LANG*}" dir="{!dir}">
			<head>
				<title><xsl:value-of select="/rss/channel/title" disable-output-escaping="yes" /></title>
				<meta name="GENERATOR" content="{$BRAND_NAME*}" />
				<script type="text/javascript" src="{JAVASCRIPT_XSL_MOPUP*}"></script>
				<xsl:element name="meta">
					<xsl:attribute name="name"><xsl:text>description</xsl:text></xsl:attribute>
					<xsl:attribute name="content"><xsl:value-of select="/rss/channel/title" /></xsl:attribute>
				</xsl:element>
				{$CSS_TEMPCODE}
				<meta http-equiv="Content-Type" content="application/xhtml+xml; charset={!charset}" />
				<meta http-equiv="Content-Script-Type" content="text/javascript" />
				<meta http-equiv="Content-Style-Type" content="text/css" />
			</head>
			<body class="re_body" onload="go_decoding();">
				<div id="cometestme" style="display: none">
					<xsl:text disable-output-escaping="yes">&amp;amp;</xsl:text>
				</div>
				<div class="rss_main">
					<xsl:element name="a">
						<xsl:attribute name="href"><xsl:value-of select="/rss/channel/image/link" /></xsl:attribute>
						<xsl:element name="img">
							<xsl:attribute name="src"><xsl:value-of select="/rss/channel/image/url" /></xsl:attribute>
							<xsl:attribute name="alt"><xsl:value-of select="/rss/channel/title" /></xsl:attribute>
							<xsl:attribute name="title"><xsl:value-of select="/rss/channel/image/title" /></xsl:attribute>
						</xsl:element>
					</xsl:element>
				</div>
				<br class="tiny_linebreak" />

				<div class="rss_main_internal">
					{+START,BOX,<span name="decodeable"><xsl:value-of disable-output-escaping="yes" select="/rss/channel/title" /></span>}
						<p id="xslt_introduction">{!RSS_XSLT_INTRODUCTION}</p>
						<xsl:apply-templates select="/rss/channel" />
						<p class="rss_copyright"><span name="decodeable"><xsl:value-of select="/rss/channel/copyright" disable-output-escaping="yes" /></span></p>
					{+END}
				</div>
			</body>
		</html>
	</xsl:template>
	<xsl:template match="channel">
		<xsl:apply-templates select="item" />
	</xsl:template>
	<xsl:template match="item">
		<h2>
			<xsl:element name="a">
				<xsl:attribute name="href"><xsl:value-of select="link" /></xsl:attribute>
				<span name="decodeable"><xsl:value-of select="title" disable-output-escaping="yes" /></span>
			</xsl:element>
		</h2>
		<p><span name="decodeable"><xsl:value-of select="description" disable-output-escaping="yes" /></span></p>
		<cite>
			<xsl:text>{!_SUBMITTED_BY} </xsl:text>
			<xsl:value-of select="author" />
			<xsl:text>, "</xsl:text>
			<xsl:value-of select="category" />
			<xsl:text>", </xsl:text>
			<xsl:value-of select="pubDate" />
		</cite>
	</xsl:template>
</xsl:stylesheet>
