<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:xhtml="http://www.w3.org/1999/xhtml">

  <xsl:output method="html" indent="yes" encoding="utf-8" />

  <xsl:template match="/">
    <html lang="en">
      <head>
        <title>Sitemap</title>
        <style>
          * { margin: 0; padding: 0; box-sizing: border-box; }
          body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #333; max-width: 1200px; margin: 0 auto; padding: 2rem; }
          h1 { font-size: 1.5rem; margin-bottom: .25rem; }
          p.meta { color: #666; font-size: .875rem; margin-bottom: 1.5rem; }
          table { width: 100%; border-collapse: collapse; font-size: .875rem; }
          th { text-align: left; padding: .5rem .75rem; background: #f5f5f5; border-bottom: 2px solid #ddd; font-weight: 600; position: sticky; top: 0; }
          td { padding: .5rem .75rem; border-bottom: 1px solid #eee; vertical-align: top; }
          tr:hover td { background: #fafafa; }
          a { color: #1a6bca; text-decoration: none; }
          a:hover { text-decoration: underline; }
          .langs { font-size: .75rem; color: #888; margin-top: .25rem; }
          .langs a { color: #888; margin-right: .5rem; }
          .langs a:hover { color: #1a6bca; }
          .priority { text-align: center; }
        </style>
      </head>
      <body>
        <h1>Sitemap</h1>
        <p class="meta">
          <xsl:value-of select="count(sitemap:urlset/sitemap:url)" /> URLs
        </p>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>URL</th>
              <th>Last modified</th>
              <th class="priority">Priority</th>
            </tr>
          </thead>
          <tbody>
            <xsl:for-each select="sitemap:urlset/sitemap:url">
              <tr>
                <td><xsl:value-of select="position()" /></td>
                <td>
                  <a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc" /></a>
                  <xsl:if test="xhtml:link">
                    <div class="langs">
                      <xsl:for-each select="xhtml:link">
                        <a href="{@href}"><xsl:value-of select="@hreflang" /></a>
                      </xsl:for-each>
                    </div>
                  </xsl:if>
                </td>
                <td><xsl:value-of select="substring(sitemap:lastmod, 1, 10)" /></td>
                <td class="priority"><xsl:value-of select="sitemap:priority" /></td>
              </tr>
            </xsl:for-each>
          </tbody>
        </table>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
