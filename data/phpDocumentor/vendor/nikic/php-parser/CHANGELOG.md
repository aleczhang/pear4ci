Version 0.9.3-dev
-----------------

* [BC] As `list()` in `foreach` is now supported the structure of list assignments changed:

  1. There is no longer a dedicated `AssignList` node; instead a normal `Assign` node is used with a `List` as  `var`.
  2. Nested lists are now `List` nodes too, instead of just arrays.

* [BC] As arbitrary expressions are allowed in `empty()` now its subnode was renamed from `var` to `expr`.

* [PHP 5.5] Add support for arbitrary expressions in `empty()`.

* [PHP 5.5] Add support for constant array / string dereferencing.
  Examples: `"foo"[2]`, `[1, 2, 3][2]`

* [PHP 5.5] Add support for `yield` expressions. This adds a new `Yield` expression type, with subnodes `key` and
  `value`.

* [PHP 5.5] Add support for `finally`. This adds a new `finallyStmts` subnode to the `TryCatch` node. If there is no
  finally clause it will be `null`.

* [PHP 5.5] Add support for `list()` destructuring of `foreach` values.
  Example: `foreach ($coords as list($x, $y)) { ... }`

* Fix parsing of `$foo =& new Bar`. It is now properly parsed as `AssignRef` (instead of `Assign`).

Version 0.9.2 (07.07.2012)
--------------------------

* Add `Class->getMethods()` function, which returns all methods contained in the `stmts` array of the class node. This
  does not take inherited methods into account.

* Add `isPublic()`, `isProtected()`, `isPrivate()`. `isAbstract()`, `isFinal()` and `isStatic()` accessors to the
  `ClassMethod`, `Property` and `Class` nodes. (`Property` and `Class` obviously only have the accessors relevant to
  them.)

* Fix parsing of new expressions in parentheses, e.g. `return(new Foo);`.

* [BC] Due to the below changes nodes now optionally accept an `$attributes` array as the
  last parameter, instead of the previously used `$line` and `$docComment` parameters.

* Add mechanism for adding attributes to nodes in the lexer.

  The following attributes are now added by default:

   * `startLine`: The line the node started in.
   * `endLine`: The line the node ended in.
   * `comments`: An array of comments. The comments are instances of `PHPParser_Comment`
     (or `PHPParser_Comment_Doc` for doc comments).

  The methods `getLine()` and `setLine()` still exist and function as before, but internally
  operator on the `startLine` attribute.

  `getDocComment()` also continues to exist. It returns the last comment in the `comments`
  attribute if it is a doc comment, otherwise `null`. As `getDocComment()` now returns a
  comment object (which can be modified using `->setText()`) the `setDocComment()` method was
  removed. Comment objects implement a `__toString()` method, so `getDocComment()` should
  continue to work properly with old code.

* [BC] Use inject-once approach for lexer:

  Now the lexer is injected only once when creating the parser. Instead of

        $parser = new PHPParser_Parser;
        $parser->parse(new PHPParser_Lexer($code));
        $parser->parse(new PHPParser_Lexer($code2));

  you write:

        $parser = new PHPParser_Parser(new PHPParser_Lexer);
        $parser->parse($code);
        $parser->parse($code2);

* Fix `NameResolver` visitor to also resolve class names in `catch` blocks.

Version 0.9.1 (24.04.2012)
--------------------------

* Add ability to add attributes to nodes:

  It is now possible to add attributes to a node using `$node->setAttribute('name', 'value')` and to retrieve them using
  `$node->getAttribute('name' [, 'default'])`. Additionally the existance of an attribute can be checked with
  `$node->hasAttribute('name')` and all attributes can be returned using `$node->getAttributes()`.

* Add code generation features: Builders and templates.

  For more infos, see the [code generation documentation][1].

* [BC] Don't traverse nodes merged by another visitor:

  If a NodeVisitor returns an array of nodes to merge, these will no longer be traversed by all other visitors. This
  behavior only caused problems.

* Fix line numbers for some list structures.
* Fix XML unserialization of empty nodes.
* Fix parsing of integers that overflow into floats.
* Fix emulation of NOWDOC and binary floats.

Version 0.9.0 (05.01.2012)
--------------------------

First version.

 [1]: https://github.com/nikic/PHP-Parser/blob/master/doc/3_Code_generation.markdown