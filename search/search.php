<?php
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once(__DIR__ . '/bpSearch/BPSearch.php');
$config = [
//  // データを格納しているディレクトリのパス
//  'dataDirPath' => null,
//  // キャッシュを保存するディレクトリのパス
//  'cacheDirPath' => null,
//  // limit, offset の初期値を設定
//  'limit'  => 20,
//  'offset' => 0,
//  // limit, offset にかかわらずすべての結果を返す場合は true をセット
//  'return_all' => false,
//  // フィルター検索を許可するパラメータ（ `search` `rand` `from` `to` は特殊なパラメータのためフィルターに利用できません）
//  'filters' => [
//    'id' => 'eq',
//    'title' => 'like',
//    'url' => 'like',
//    'keywords' => 'eq',
//    'categoryId' => 'eq',
//    'categoryIds' => 'eq',
//    'categoryLabel' => 'eq',
//    'tagIds' => 'eq',
//    'year' => 'eq',
//    'month' => 'eq',
//    'date' => 'eq',
//    'day' => 'eq',
//    'datetime' => 'eq',
//  ],
//  // パラメータマッピング
//  'paramMapping' => [
//    'from' => 'datetime',
//    'to' => 'datetime',
//  ],
//  // ソートの基準となるパラメータとソート順を初期値（パラメータに `sortBy` または `sortOrder` がない場合は表示用データの順）
//  'sortBy' => 'datetime',
//  'sortOrder' => 'descend', // 'ascend' or 'descend'
//  // デバッグモードにする場合は true をセット
//  'devMode' => true,
//  // 処理時間を計測する場合は true をセット
  'returnTime' => true,
//  // 実行ファイルの URL を JSON に含める場合は true をセット
//  'includeScriptUrl' => true,
//  // リファラの URL を JSON に含める場合は true をセット
//  'includeRefererUrl' => true,
//  // ページネーションの情報を結果の JSON に含める場合は true をセット
//  'includePagination' => true,
//  // ページネーションに表示する最大数（奇数をセット）
//  'viewPagesLimit' => 3, // 3, 5, 7, 9 ...
];

$fs = new BPSearch($config, true);
