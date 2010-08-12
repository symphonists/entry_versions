<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output encoding="UTF-8" indent="yes" method="xml" omit-xml-declaration="yes" />

	<xsl:template match="/">
		<xsl:apply-templates select="/entries/entry[1]"/>
	</xsl:template>
	
	<xsl:template match="entry">
		<xsl:element name="entry">
			<xsl:attribute name="id"><xsl:value-of select="@id"/></xsl:attribute>
			<xsl:attribute name="version"><xsl:value-of select="$version"/></xsl:attribute>
			<xsl:attribute name="created-by"><xsl:value-of select="$created-by"/></xsl:attribute>
			<xsl:attribute name="created-date"><xsl:value-of select="$created-date"/></xsl:attribute>
			<xsl:attribute name="created-time"><xsl:value-of select="$created-time"/></xsl:attribute>
			<xsl:apply-templates select="*"/>
		</xsl:element>
	</xsl:template>
	
	<xsl:template match="*">
		<xsl:element name="{name()}">
			<xsl:apply-templates select="* | @* | text()"/>
		</xsl:element>
	</xsl:template>
	
	<xsl:template match="@*">
		<xsl:attribute name="{name(.)}">
			<xsl:value-of select="."/>
		</xsl:attribute>
	</xsl:template>
	
</xsl:stylesheet>