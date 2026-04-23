# IPP project 2026

![diagram](./diagram.png)

## OOP patterns used

- **Singleton:** aside from the required `true`, `false`, and `nil` singletons,
    all built-in classes are also implemented singleton objects, which allows
    for e.g. the `String` class to receive its input stream as a private,
    interpreter-visible attribute.

- **Template method:**
    + `Closure::executeInScope` allows `Method::executeInScope` to create
        its own scope just above the closure scope that defines `self` and
        `super`
    + `Scope::tryUpdateVariable` allows `ValidationScope::tryUpdateVariable`
        to add validation to the operation

- **Proxy:** used to wrap `SolClass` into a proxy that translates method lookups
    into static method lookups on the original `SolClass` object.
    This allows treating classes as first-class objects and implementing
    static methods.

- **Adaptor:** `ClosureObject` adapts an internal `Closure` to act as a
    `SolObject`, which allows for splitting the generic `Closure`
    (that executes actual SOL code from methods and nested blocks) from
    closure objects (that only apply to nested blocks).

## Problems resolved

- **Problem:** how to distinguish between classes and instances for message
    sending?

    **Solution:** made classes first-class objects so that there is no need to
        distinguish them from class instances.

- **Problem:** if classes are first-class objects, what are their own classes?

    **Solution:** every class as an object has an anonymous metaclass that
        redirects method lookups to the class's `staticMethods`.

- **Problem:** how to implement closures?

    **Solution:** split closure logic in two parts: code execution and
    the object wrapper part.
    Code execution is universal among methods and closures and is implemented
    in the `Closure` PHP class.
    The object wrapper part is only relevant for closure objects
    (that have `value:value:...` methods) and is implemented in the
    `ClosureObject` PHP class.

    A closure object is an object of a special anonymous class that inherits
    from the built-in `Block` class and has only one method called `value...`
    that redirects the call to the underlying closure code.

## Changelog

**Version 2:**

- `Dockerfile`:
    + fixed environment setup and code quality tool scripts
- Tester:
    + fix parser code reporting
    + fix temporary file creation: now using a standard function instead of
        creating a hidden file in the script directory

## Future thoughts

Aside from **extension #5 (classes as first-class objects)** that is already
implemented as an initial design choice, the following extensions could be
easily implemented thanks to the extensible design of the interpreter:

- **extension #1 (exceptions):**
    + classes can be passed as `on:` parameters without any problems;
    + the interpreter already does some exception handling at method boundaries
        in order to print user-friendly stack traces. Implementing the
        `on:do:` mechanism will simply mean extending an already existing
        mechanism;
    + exception type checking can be implemented using the existing
        `SolObject::isInstanceOf` method;
- **extension #2 (interfaces):**
    + the interpreter already processes the source in several passes due to
        recursive inheritance checking, thus adding a new pass to process
        interfaces before class definitions will not be a problem;
    + upon loading a class, verifying that it implements a given list
        of interfaces (which are already processed by the interpreter)
        should be trivial;
- **extension #8 (the `doesNotUnderstand` mechanism):**
    + the whole message sending logic is encapsulated in
        `final SolObject::send`, which already contains DNU handling,
        so adding new DNU logic will just mean modifying this single function.

## AI usage

- [GitHub Copilot](https://github.com/features/copilot):
    + in-editor automatic code completion, all results always reviewed and fixed
        by hand
    + *not used* in agentic or chat mode
- [ChatGPT](https://chatgpt.com) (see [chat history](https://chatgpt.com/share/69d80dfe-71c0-832e-b4f1-c5063ea175f3)):
    + generic PHP & TypeScript questions (language semantics, library functions,
        tool usage)
    + resolving specific coding problems (warnings & errors)
    + naming suggestions (functions & classes)
    + occasional grammar & spelling assistance
