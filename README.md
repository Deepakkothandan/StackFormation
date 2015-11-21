# StackFormation

**Lightweight AWS CloudFormation Stack, Template and Parameter Manager and Preprocessor**

Author: [Fabrizio Branca](https://twitter.com/fbrnc)

### Stack Configuration

Create a `stacks.yml` in your current directory

```
stacks:
    my-stack:
        template: templates/my-stack.template
        parameters:
            foo: 42
            bar: 43
    my-second-stack:
        template: templates/my-stack.template
        parameters:
            foo: 42
            bar: 43
```

### Parameter Values

- Empty value: keep previous value (when updating existing stack)
- Output lookup: `output:<stack>:<output>` -> output value
- Resource lookup: `resource:<stack>:<logicalResource>` -> physical Id of that resource

Output and resource lookup allow you to "connect" stacks to each other by wiring the output or resources created in
one stacks to the input paramaters needed in another stack that sits on top of the first one without manually 
managing the input values.

Example
```
stacks:
    stack1-db:
        template: templates/stack1.template
        [...]
    stack2-app:
        template: templates/stack2.template
        parameters:
            db: 'output:stack1-db:DatabaseRds'
```

### AWS SDK

StackFormation uses the AWS SDK for PHP. You should configure your keys in env vars:
```
export AWS_ACCESS_KEY_ID=INSERT_YOUR_ACCESS_KEY
export AWS_SECRET_ACCESS_KEY=INSERT_YOUR_PRIVATE_KEY
export AWS_DEFAULT_REGION=eu-west-1
```

### Function `Fn::FileContent`

Before uploading CloudFormation template to the API there's some pre-processing going on:
I've introduced a new function "FileContent" that accepts a path to a file. This file will be read, converted into JSON (using `Fn::Join`).
The path is relative to the path of the current CloudFormation template file.

Usage Example:
```
    [...]
    "UserData": {"Fn::Base64": {"Fn::FileContent":"../scripts/setup.sh"}},
    [...]
```

### Inject Parameters

The scripts (included via `Fn::FileContent`) may contain references to other CloudFormation resources or parameters. 
Part of the pre-processing is to convert snippets like `{Ref:MagentoWaitConditionHandle}` or `{Ref:AWS::Region}` (note the missing quotes!)
into correct JSON snippets and embed them into the `Fn::Join` array.

Usage Example:
```
#!/usr/bin/env bash
/usr/local/bin/cfn-signal --exit-code $? '{Ref:WaitConditionHandle}'
```
will be converted to:
```
{"Fn::Join": ["", [
"#!\/usr\/bin\/env bash\n",
"\/usr\/local\/bin\/cfn-signal --exit-code $? '", {"Ref": "WaitConditionHandle"}, "'"
]]}
```

### Commands

- stack:list
- stack:deploy
- stack:delete
- stack:observe

### PHP 

Deploy a stack:
```
require_once __DIR__ . '/vendor/autoload.php';

$stackmanager = new \StackFormation\StackManager();
$stackmanager->deployStack('my-stack');
```
