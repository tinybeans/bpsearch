# Movable Type での導入方法

## 免責

当ドキュメント及びプラグイン等により発生した不利益に関しては、いかなる責任も負いませんので予めご了承ください。  
それを承諾された場合のみ下記のドキュメントに沿って作業してください。

## ファイルを設置

任意のディレクトリに下記のディレクトリ構成になるようにファイルをアップロードします。

```
.
└── search
    ├── cache
    ├── data
    │   ├── all.json
    │   └── all.txt
    ├── bpSearch
    │   └── BPSearch.php
    └── search.php
```

ここでは、 `search.php` の URL が https://tinybeans.net/blog/search/search.php と仮定して説明します。

## all.json と all.txt を出力 

インデックステンプレートで `all.json` を出力します。サンプルのテンプレートは下記の通りです。

* テンプレート名：bpSearch-data
* 出力ファイル名：search/data/all.txt

なお、下記のテンプレートでは再構築の際のパフォーマンスを考慮して、 `WriteToFile` プラグインを利用しています。

* [WriteToFile \- Movable Type Plugins](https://www.h-fj.com/mtplugins/writetofile.php)

```xml
<mt:Ignore>==================================================
Template Name : bpSearch-data
Template Type : index
Template Note : WriteToFile プラグイン必須
==================================================</mt:Ignore>
<mt:SetVars note="初期設定">
json_path =<mt:BlogSitePath />search/data/all.json
cache_path =<mt:BlogSitePath />search/cache/.htaccess
</mt:SetVars>
<mt:For compress="2" trim="1">

  <mt:SetVar name="items" note="初期化" />
  <mt:SetVar name="response" note="初期化" />

  <mt:Entries lastn="0">
    <mt:SetVars>
    entry     =<mt:TemplateNote note="初期化" />
    blog_id   =<mt:BlogID />
    blog_name =<mt:BlogName />
    pattern1  =/[（）「」【】｛｝：\/。、"'\.■]|&.*?;/g
    pattern2  =/だから、|それで、|そのため、|そこで、|したがって、|従って、|すると、|それなら、|それでは、|しかし、|ところが、|のに、|なのに、|それなのに、|にもかかわらず、|ものの、|とはいうものの、|でも、|それでも、|また、|ならびに、|および、|かつ、|そして、|それに、|それから、|しかも、|そのうえ、|それどころか、|どころか、|そればかりか、|そればかりでなく、|一方、|逆に、|反対に、|または、|それとも、|あるいは、|もしくは、|なぜなら、|というのは、|なお、|ただし、|だた、|もっとも、|つまり、|すなわち、|例えば、|いわば、|それでは、|ところで、|あります|かもしれません|やはり|です。|なりました。|でしょう。|(あり|かもしれ)?ません。|(い|して|し|り|られ|いた|な|言え|いただけ)?ます。|(ある|いる|ない)?ため、|なく、|(くだ|下)さい。/g
    </mt:SetVars>

    <mt:Ignore>==============================

    データセット

    ==============================</mt:Ignore>
    <mt:EntryID setvar="entry_id" />
    <mt:SetVarBlock name="items_key">e<mt:Var name="entry_id" /></mt:SetVarBlock>

    <mt:Ignore>------------------------------
    標準のフィールド（JSON用）
    -----------------------------</mt:Ignore>
    <mt:SetVar      name="entry" key="id" value="$entry_id" />
    <mt:SetVarBlock name="entry" key="title"><mt:EntryTitle remove_html="1" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="keywords"><mt:EntryKeywords remove_html="1" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="excerpt"><mt:EntryExcerpt remove_html="1" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="url"><mt:EntryPermalink remove_host="1" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="year"><mt:EntryDate format="%Y" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="month"><mt:EntryDate format="%m" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="date"><mt:EntryDate format="%d" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="day"><mt:EntryDate format="%A" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="datetime"><mt:EntryDate format="%Y%m%d%H%M%S" /></mt:SetVarBlock>

    <mt:Ignore>=== メインカテゴリ ===</mt:Ignore>
    <mt:EntryPrimaryCategory>
      <mt:SetVarBlock name="entry" key="categoryId"><mt:CategoryID /></mt:SetVarBlock>
      <mt:SetVarBlock name="entry" key="categoryLabel"><mt:CategoryLabel encode_php="1" /></mt:SetVarBlock>
    </mt:EntryPrimaryCategory>
    <mt:Ignore>=== END メインカテゴリ ===</mt:Ignore>

    <mt:Ignore>=== カテゴリ ===</mt:Ignore>
    <mt:SetVar name="category_ids" />
    <mt:EntryCategories>
      <mt:CategoryID setvar="cat_id" />
      <mt:SetVar name="category_ids" function="push" value="$cat_id" />
    </mt:EntryCategories>
    <mt:SetVar name="entry" key="categoryIds" value="$category_ids" />
    <mt:Ignore>=== END カテゴリ ===</mt:Ignore>

    <mt:Ignore>=== タグ ===</mt:Ignore>
    <mt:SetVar name="tag_ids" />
    <mt:EntryTags>
      <mt:TagID setvar="tag_id" />
      <mt:SetVar name="tag_ids" function="push" value="$tag_id" />
    </mt:EntryTags>
    <mt:SetVar name="entry" key="tagIds" value="$tag_ids" />
    <mt:Ignore>=== END タグ ===</mt:Ignore>

    <mt:Ignore>=== カスタムフィールド ===</mt:Ignore>
    <mt:Ignore>=== END カスタムフィールド ===</mt:Ignore>

    <mt:SetVar name="items" key="$items_key" value="$entry" />

    <mt:Ignore>------------------------------
    標準のフィールド（テキスト用）
    -----------------------------</mt:Ignore>
    <mt:SetVarBlock name="entry" key="body"><mt:EntryBody strip_linefeeds="1" regex_replace="/\t/gu","" remove_html="1" compress="3" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="more"><mt:EntryMore strip_linefeeds="1" regex_replace="/\t/gu","" remove_html="1" compress="3" /></mt:SetVarBlock>
    <mt:SetVarBlock name="entry" key="excerpt"><mt:EntryExcerpt strip_linefeeds="1" regex_replace="/\t/gu","" remove_html="1" no_generate="1" compress="3" /></mt:SetVarBlock>

    <mt:Ignore>=== カスタムフィールド ===</mt:Ignore>
    <mt:Ignore>=== END カスタムフィールド ===</mt:Ignore>

    <mt:Ignore>==============================

    出力（text）

    ==============================</mt:Ignore>
    <mt:SetVarBlock name="entry_text"><mt:Loop name="entry"><mt:Var name="__value__" compress="3" /></mt:Loop></mt:SetVarBlock>
    <mt:Var name="entry_text" regex_replace="$pattern1","" regex_replace="$pattern2","_" setvar="entry_text" />
    <mt:Var name="entry_id" /><mt:Unless regex_replace="/ /","\t"> </mt:Unless><mt:Var name="entry_text" compress="3" />

  </mt:Entries>

  <mt:Ignore>==============================

  出力（JSON）

  ==============================</mt:Ignore>
  <mt:SetVar name="response" key="items" value="$items" />
  <mt:WriteToFile file="$json_path">
    <mt:Var name="response" to_json="1" />
  </mt:WriteToFile>
  <mt:WriteToFile file="$cache_path">Deny from all</mt:WriteToFile>

</mt:For>
```

このテンプレートを再構築して、 https://tinybeans.net/blog/search/search.php にアクセスして JSON が表示されれば設置完了です。

## キャッシュのクリア

### テンプレートにキャッシュをクリアする処理を埋め込む

#### SystemCommand プラグインをインストール

[tokiwatch/SystemCommand](https://github.com/tokiwatch/SystemCommand) のプラグインを Movable Type にインストールします。インストール方法や設定方法については SystemCommand プラグインの README ファイルで小確認ください。

#### インデックステンプレートを作成

下記のインデックステンプレートを作成します。

このテンプレートでは、 `command` 変数に入れた文字列が、システムコマンドとして再構築時に実行されます。したがって、はじめに `execute` 変数を `0` のまま再構築し、出力されたコマンドにな違いがないか確認した後、 `execute` 変数に `1` をセットして再構築されるようにしてください。

* テンプレート名：bpSearch-clear-cache
* 出力ファイル名：search/clear-cache.log

```xml
<mt:Ignore>==================================================
Template Name : bpSearch-clear-cache
Template Type : index
Template Note : SystemCommand プラグイン必須
==================================================</mt:Ignore>

<mt:SetVars>
cache_path =<mt:BlogSitePath />search/cache/*
execute    =0
</mt:SetVars>

<mt:SetVars>
command =rm -rf <mt:Var name="cache_path" />
</mt:SetVars>

<mt:If name="execute">
  <mt:System command="$command" return="1" />
<mt:Else>
  <mt:Var name="command" />
</mt:If>
```

このテンプレートが実行される際にキャッシュファイルがクリアされます。
