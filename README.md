# BPSearch.php

## 事前に確認しておくと良いこと

### 一般

* PHP は使えますか？
* PHP が使える場合、PHP から Linux のシステムコマンドの grep が使えますか？（通常は使えます）
* 検索結果のレンダリングには Vue.js が便利です。

### Movable Type の場合

* WriteToFile プラグインを使うと再構築が効率よく行えます。  
[WriteToFile \- Movable Type Plugins](https://www.h-fj.com/mtplugins/writetofile.php)
* bpSearch のキャッシュ機能を使う場合、キャッシュのクリア用に SystemCommand プラグインを使うと便利です。    
[tokiwatch/SystemCommand](https://github.com/tokiwatch/SystemCommand)

## 設置方法

ウェブサイトの任意のディレクトリに下記のディレクトリ構成になるようにファイルをアップロードします。

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
### data/all.json

`data` ディレクトリ内の `all.json` はあらかじめ CMS 等で書き出してください。

`all.json` には、ルートのプロパティとなる `items` の中に、そのコンテンツを特定できるキーをキー、フィルターや検索結果の表示に必要な情報を値として書き出してください。

```json
{
  "items": {
    "e10": {
      "categoryId": "18",
      "categoryIds": [
        "18"
      ],
      "categoryLabel": "Events",
      "date": "13",
      "datetime": "20070813121308",
      "day": "月曜日",
      "excerpt": "（株）エムディエヌコーポレーションweb creators編集部が主宰する『Designer meets Designers 01』（以下「D2」）に参加してきました。...",
      "id": "10",
      "keywords": "CSS,セミナー,MDN",
      "month": "08",
      "tagIds": [
        "14",
        "70"
      ],
      "title": "Designer meets Designers 01",
      "url": "/blog/2007/08/designer-meets-designers-01.html",
      "year": "2007"
    },
    "e101": {
      "categoryId": "1",
      "categoryIds": [
        "1"
      ],
      "categoryLabel": "Diary",
      "date": "13",
      "datetime": "20080313055226",
      "day": "木曜日",
      "excerpt": "『ホップ本』といわれている（らしい）『実践 Web Standards Design』を買いました。 この本も、『Web標準の教科書』と同様、評価の高い本なので楽しみです。...",
      "id": "101",
      "keywords": "Web標準,ホップ本",
      "month": "03",
      "tagIds": [
        "39"
      ],
      "title": "「ホップ本」買いました",
      "url": "/blog/2008/03/post-14.html",
      "year": "2008"
    }
  }
}
```

### data/all.txt

`data` ディレクトリ内の `all.txt` はあらかじめ CMS 等で書き出してください。

`all.txt` には、キーワード検索の対象としたいテキストを、1コンテンツあたり1行として書き出してください。

また、行の先頭にはそのコンテンツを特定できるキーを入れ、タブで区切ってキーワード検索対象のテキストを続けてください。

```text
3608	303Movable Type 7 の管理画面で Data API v4 経由でコンテンツデータを更新するときにうまくいかずにハマった点を共有します091120181109111955MT 7 の管理画面で下記のように実行して 取扱店舗 というコンテンツデータフィールドの関連付けを削除したり変更したりしたかったのですがどうにもコンテンツデータが更新できませんでしたmtappVarsDataAPIgetToken(function () {  mtappVarsDataAPIgetContentData(31, 3, 3, function (cd) {    for (let i = 0; i  cddatalength; i++) {      if (cddata[i]label === 取扱店舗) {        cddata[i]data = [];      }    }    mtappVarsDataAPIupdateContentData(31, 3, 3, cd, function (newCd) {      consolewarn(newCd);    });  });});ここでPOST されたデータを見てみるとcontent_data: {author:{displayName:tinybeans,id:1,userpicUrl:null},basename:06bed0e01cb73fa5040e2e726f03de7335617791,blog:{id:31},createdDate:2018-10-26T11:14:03 09:00,data:[{\data\:[],\id\:\41\}],date:2018-10-19T06:51:14 09:00,id:3,label:桜並木を自宅で楽しめる歴史ある建築物件,modifiedDate:2018-11-09T02:04:40 09:00,status:Publish,unpublishedDate:null,updatable:true}更新したい部分だけ data:[{\data\:[],\id\:\41\}] のように値がオブジェクト全体が文字列になっていましたそこで下記のようにすべて文字列にしてから送信したら成功しましたmtappVarsDataAPIgetToken(function () {  mtappVarsDataAPIgetContentData(31, 3, 3, function (cd) {    for (let i = 0; i  cddatalength; i++) {      if (cddata[i]label === 取扱店舗) {        cddata[i]data = [];      }    }    const param = ObjecttoJSON(cd);    mtappVarsDataAPIupdateContentData(31, 3, 3, param, function (newCd) {      consolewarn(newCd);    });  });});結構な時間ハマったので共有しておきます以上ですMovable Type 7, Data API, コンテンツデータ金曜日blog20181109-111955html3608337100Data API v4 でコンテンツデータを更新するときにハマった管理画面Data API303307132018
3607	307Movable Type 7 に対応した MTAppjQuery v220 をリリースしました200920180920120751MTAppjQuery v220 をリリースしましたマルチフィールドが記事ウェブページでも利用可能にこのアップデートによりマルチフィールドが記事ウェブページでも利用できるようになりましたMovable Type 7 のブロックエディタは記事とウェブページでは利用できないのでぜひマルチフィールドの利用をご検討ください個人的にはMT 7 では記事ウェブページは利用せずにコンテンツデータに寄せていくのがいいと思っていたので初めは対応していませんでたが実際の現場ではまだ記事は頻繁に利用されているようですので今回対応させましたマルチフィールドが縦向きの固定テーブルに対応ご要望の多かった1列目が項目名2列目が入力欄で行を固定という縦向きの固定テーブルに対応しましたこれでこれまで重宝されてきた MTAppJSONTable はその役目を終え mtappmultiField を使っていくのが良いかなと思いますそのほか mtappmultiField で下記の対応を行いましたフィールドを追加 ボタンの表示・非表示を制御する showAddFieldButton オプションを追加保存データを表示 ボタンの表示・非表示を制御する showViewRawDataButton オプションを追加また MTAppAssetFields() を Movable Type 7 に対応しましたこのメソッドはプラグインの設定 で 旧バージョンのメソッド を 有効 にすることで利用できますダウンロードすでにライセンスをお持ちの方はサポートサイトの製品ダウンロードのページからダウンロードできます引き続きよろしくお願いいたします！MTAppjQuery, Movable Type, プラグイン, 管理画面, カスタマイズ木曜日blog20180920-120751html3607104221MTAppjQuery v220 リリース - マルチフィールドが記事・ウェブページに対応MTAppjQuery307132018
```


### cache ディレクトリ

`cache` ディレクトリは PHP が書き込めるようにパーミッションを設定してください。 


## 設定

`search.php` の中の `$config` の値を変更して設定します。設定する項目とその内容はコード中のコメントを参照してください。

### 検索条件の設定

各パラメータの検索条件は、 `search.php` の中の `$config` の `filters` プロパティで設定します。この `filters` プロパティで設定されていないパラメータは、検索の際は無視されます。

**`$config['filters']` の設定は必須です。** 

設定できる検索条件は下記の通りです。

* `eq` : 完全一致検索（初期値）
* `like` : パラメータの値を含む場合はヒット
* `lt` : パラメータの値よりも小さいものがヒット
* `le` : パラメータの値よりも小さいか等しいものがヒット
* `gt` : パラメータの値よりも大きいものがヒット
* `ge` : パラメータの値よりも大きいか等しいものがヒット
* `not` : パラメータと一致するものは除外

## search.php に渡すパラメータ仕様

### search

キーワード検索を行います（like 検索）。複数のキーワードを半角スペースで区切って渡した場合は `AND` 検索となります。

### limit

取得する件数の上限を指定します。

### offset

先頭から `offset` で指定した件数を除いて結果を返します。

### rand

`rand=foo` を指定すると、検索結果が `limit` に含まれなかった場合は `rand` パラメータの値に `foo` をもつ記事からランダムに抽出して `limit` の数まで埋めます。つまり、ランダム取得の対象にしたい記事に予め `rand=['foo', 'bar']` という配列を持たせておく必要があります。

例えば、検索条件に満たなかったとき、 `rand=rel` が指定されている記事からランダムに取得して埋めたい場合は下記のように指定します。

```
?rand=rel
```

### cache

`cache=1` が付いている検索結果の JSON は `cache` ディレクトリにファイルとして出力されます。次回以降は、キャッシュファイルがある場合はそのファイルの JSON を返却します。

`cache` ディレクトリの中のファイルは、 cron で定期的に削除するか、CMS のプラグイン等で削除するようにします。

### その他のキー

JSON に含まれる項目をキー・バリューで指定します。

例えば、記事IDが `123` の記事を取得する場合は、 `id=123` と指定します（完全一致フィルター）。

**値が空の記事**を取得したい場合は `foo=:empty:` と指定し、**値が空でない記事**を取得する場合は `foo=:notempty:` と指定します。

複数の値の中にどれかに一致する、すなわち `OR` 条件になるパラメータは配列で渡します。

```
?category[]=blog&category[]=news
```

#### その他のキーの検索条件について

**検索条件について**

`設定` セクションの `検索条件の設定` の項を参照してください。

**パラメータとJSONの値の両方が配列の場合**

`tagsIds[]=111&tagsIds[]=222` の場合は `tagsIds` が `111` または `222` を含めばヒット（ `OR` 検索）

```
JSON の値のサンプル

"tagIds": [ "111", "222", "333" ],
```

**パラメータが配列、JSONの値は文字列の場合**

`categoryLabel[]=Blog&categoryLabel[]=News` の場合は `categoryLabel` が `Blog` か `News` であればヒット

```
JSON の値のサンプル

"categoryLabel": "Blog",
```

**パラメータが文字列、JSONの値が配列の場合**

`categoryIds=123` で JSON の値が配列の場合は `categoryIds` が JSON の `categoryIds` の配列の中にあればヒット

```
JSON の値のサンプル

"categoryIds": [ "123", "307", "333" ],
```
