<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create $activeCampaignService service

const TOKEN = 'secret_EpsZs6ckSNEUOOSJldbObvxJ0yogCKnuhOjsoC8BVHO';
const DB_ID = '258b9c1311c041eabdec57dbb8e1032e';
const PAGE_ID = 'cbd44053abbd447dad50359652aa611b';

function scenario1()
{
    $client = new \BrizyForms\NativeService\NotionNativeService(TOKEN);
    $db = $client->getDatabase(DB_ID);
    $newPageContent = '
{
  "parent": {
    "database_id": ' . PAGE_ID . '
  },
  "icon": {
    "type": "emoji",
    "emoji": "🎉"
  },
  "cover": {
    "type": "external",
    "external": {
      "url": "https://website.domain/images/image.png"
    }
  },
  "properties": {
    "Name": {
      "title": [
        {
          "text": {
            "content": "Tuscan Kale"
          }
        }
      ]
    },
    "Description": {
      "text": [
        {
          "text": {
            "content": "A dark green leafy vegetable"
          }
        }
      ]
    },
    "Food group": {
      "select": {
        "name": "🥦 Vegetable"
      }
    },
    "Price": {
      "number": 2.5
    }
  },
  "children": [
    {
      "object": "block",
      "type": "heading_2",
      "heading_2": {
        "text": [
          {
            "type": "text",
            "text": {
              "content": "Lacinato kale"
            }
          }
        ]
      }
    },
    {
      "object": "block",
      "type": "paragraph",
      "paragraph": {
        "text": [
          {
            "type": "text",
            "text": {
              "content": "Lacinato kale is a variety of kale with a long tradition in Italian cuisine, especially that of Tuscany. It is also known as Tuscan kale, Italian kale, dinosaur kale, kale, flat back kale, palm tree kale, or black Tuscan palm.",
              "link": {
                "url": "https://en.wikipedia.org/wiki/Lacinato_kale"
              }
            }
          }
        ]
      }
    }
  ]
}';

//    $newPage = $client->createPage(json_decode($newPageContent, true));
    $newPage = $client->createPage($newPageContent);
    $page = $client->getPage(PAGE_ID);

    return ['DB' => $db, 'page' => $page];
}

$s = scenario1();
print_r($s);
