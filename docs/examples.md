# Beispiel: ASTOperation ausschließlich mit JSON und fromJson/toJson

Dieses Beispiel zeigt, wie du das AST-Paket ausschließlich mit dem JSON-Format von `ASTOperation`
verwendest. Alle Operationen werden als JSON-Strings definiert, mit `ASTOperation::fromJson` geladen
und mit `ASTAdapter::modify` angewendet. Das Ergebnis kann mit `ASTOperation->toJson()` wieder als
JSON ausgegeben werden.

## Beispiel-Code

```php
use Bono\AST\ASTAdapter;
use Bono\AST\ASTOperation;

$ast = [];

// 1. Klasse hinzufügen
$json = '{
  "type": "add_class",
  "params": {
    "name": "JsonDemoClass"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 2. Property hinzufügen
$json = '{
  "type": "add_property",
  "params": {
    "class": "JsonDemoClass",
    "name": "counter",
    "type": "int",
    "visibility": "private",
    "default": 0
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 3. Methode mit if/switch/return
$json = '{
  "type": "add_method",
  "params": {
    "class": "JsonDemoClass",
    "name": "process",
    "code": "public function process(int $value): string {\\n    if ($value < 0) { return \'negativ\'; }\\n    switch ($value) {\\n        case 0: return \'null\';\\n        case 1: return \'eins\';\\n        default: return \'etwas anderes\';\\n    }\\n}"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 4. Methode mit allen PHP-Typen und Funktionsaufruf
$json = '{
  "type": "add_method",
  "params": {
    "class": "JsonDemoClass",
    "name": "callAllTypes",
    "code": "public function callAllTypes(int $a, float $b, bool $c, string $d, array $e, object $f): void {\\n    var_dump($a, $b, $c, $d, $e, $f);\\n}"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 5. DocBlock hinzufügen
$json = '{
  "type": "add_docblock",
  "params": {
    "class": "JsonDemoClass",
    "target": "process",
    "docblock": "/**\\n * Verarbeitet einen Wert.\\n * @param int $value\\n * @return string\\n */"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 6. Kommentar hinzufügen
$json = '{
  "type": "add_comment",
  "params": {
    "class": "JsonDemoClass",
    "target": "counter",
    "comment": "// Zähler für die Demo"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 7. Use-Statement hinzufügen
$json = '{
  "type": "add_use_statement",
  "params": {
    "use": "DateTime"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 8. Methoden- und Klassennamen ändern
$json = '{
  "type": "rename_method",
  "params": {
    "class": "JsonDemoClass",
    "oldName": "process",
    "newName": "processValue"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

$json = '{
  "type": "rename_class",
  "params": {
    "oldName": "JsonDemoClass",
    "newName": "RenamedJsonDemoClass"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 9. Property und Methode entfernen
$json = '{
  "type": "remove_property",
  "params": {
    "class": "RenamedJsonDemoClass",
    "name": "counter"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

$json = '{
  "type": "remove_method",
  "params": {
    "class": "RenamedJsonDemoClass",
    "name": "callAllTypes"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 10. Klasse entfernen
$json = '{
  "type": "remove_class",
  "params": {
    "name": "RenamedJsonDemoClass"
  }
}';
$op = ASTOperation::fromJson($json);
$ast = ASTAdapter::modify($ast, $op->type, $op->params);

// 11. Operation als JSON ausgeben
$jsonResult = $op->toJson();
echo $jsonResult;

// 12. PHP-Code generieren
$phpCode = ASTAdapter::toPhp($ast);
echo $phpCode;
```

## Hinweise

- Jede Operation wird als JSON-String definiert und mit `ASTOperation::fromJson` geladen.
- Das Ergebnis kann mit `$op->toJson()` wieder als JSON ausgegeben werden.
- Kontrollstrukturen und alle PHP-Typen werden als Code-Snippet übergeben.
- Die API ist so gestaltet, dass sie unabhängig von PhpParser direkt mit JSON arbeitet.

