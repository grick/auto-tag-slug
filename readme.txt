=== Plugin Name ===
Contributors: grick
Tags: tag, tags, slug, URL, rewrite, Bing, permalink, SEO, 标签, 别名
Requires at least: 2.7.0
Tested up to: 3.2.1
Stable tag: 0.5.0

Generate URL-friendly tag slug. Especially useful for non-English speaking country users.

== Description ==

This plugin will convert post tags slug to Pinyin or English words.

For example, you may have tag URL like this: www\.abc.com/tag/`%e4%bd%a0%e5%a5%bd`

With this plugin, you can convert all of them to the following format:

*	www\.abc.com/tag/`ni-hao`
*	www\.abc.com/tag/`hello`

These URL have better looks and should be more Search Engine friendly.

= Main Feature =

* One click to convert all post tags slug
* Select "Pin Yin" or "English" for slug format
* Convert post tags slug automatically when new post save or update
* Reset all tags slug to default

= 中文指南 =

* 插件名称：WordPress 标签别名转换（Auto Tag Slug）
* 插件描述：此插件能够自动修改系统标签别名至拼音或英语单词格式。
* 适用对象：中文版或其他非英语系国家用户
* 当前版本：0.5.0

= 插件简介 =

此插件的初衷是为了解决 IIS6 上经过 Rewrite 的 tag 的解析问题，使用后可以将 WordPress 标签原生的 urlencode 格式转换为汉语拼音或者英语单词。

* 原来的 URL：www\.abc.com/tag/`%e4%bd%a0%e5%a5%bd`
* 拼音转换后的 URL：www\.abc.com/tag/`ni-hao`
* 英语单词转换后的 URL：www\.abc.com/tag/`hello`

= 主要功能 =

* 支持日志发布或更新时自动转换标签别名
* 支持一键转换全部标签功能
* 支持恢复原有格式




== Installation ==

1. Upload `auto-tag-slug` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Why use this plugin? =

If you are non English country user, This plugin can make beautiful url for your tags.It is also able to solve some rewrite problem on IIS6.

== Screenshots ==

1. Screen shot
2. 设置截图

== Changelog ==

= 0.5.0 =
Initial release.

