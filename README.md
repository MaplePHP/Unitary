# MaplePHP Unitary — Fast Testing, Full Control, Zero Friction

Unitary is a modern PHP testing framework for developers who value speed, precision, and full control. No configuration. No clutter. Just a clean, purpose-built core that can execute 100,000+ tests per second and scales smoothly from quick sanity checks to full integration suites.

Mocking, validation, and assertions are built in from the start, with no plugins, adapters, or bootstrapping required. The CLI is fast and intuitive, the workflow is consistent, and getting started takes seconds. Whether you are validating a single function or an entire system, Unitary lets you move quickly and test with confidence.

![Prompt demo](http://wazabii.se/github-assets/unitary/unitary-cli-states.png)

_Do you like the CLI theme? [Download it here](https://github.com/MaplePHP/DarkBark)_


### Familiar Syntax. Fast Feedback.

Unitary is designed to feel natural for developers. With clear syntax, built-in validation, and zero setup required, writing tests becomes a smooth part of your daily flow and not a separate chore.

```php
use MaplePHP\Unitary\TestCase;

group("Your grouped test subject", function (TestCase $case) {

    $json = '{"response":{"status":200,"message":"ok"}}';

    $case->expect($json)
         ->isJson()
         ->hasJsonValueAt("response.status", 200)
         ->validate();
});
```

---

## Next-Gen PHP Testing Framework

Unitary is a blazing-fast, developer-first testing framework for PHP, built from the ground up with zero third-party dependencies and a highly optimized core, not just a wrapper around legacy tools. It’s simple to get started, lightning-fast to run, and powerful enough to test everything from units to mocks.

> 🚀 *Test 100,000+ cases in \~1 second. No config. No bloat. Just results.*

---

## 🔧 Why Use Unitary?

* **Works out of the box** – No setup, no config files.
* **Not built on PHPUnit** – Unitary is a standalone framework.
* **100% agnostic** – Every sub-library is purpose-built for speed and control.
* **First-class CLI** – Intuitive test runner that works across platforms.
* **Powerful validation** – Built-in expectation engine, assert support, and structured output.
* **Mocking included** – No external mocking libraries needed.
* **Super low memory usage** – Ideal for local runs and parallel CI jobs.

---

## ⚡ Blazing Fast Performance

Unitary runs large test suites in a fraction of the time — even **100,000+** tests in just **1 second**.

🚀 That’s up to 46× faster than the most widely used testing frameworks. 

---


## Getting Started (Under 1 Minute)

Set up your first test in three easy steps:

### 1. Install

```bash
composer require --dev maplephp/unitary
```

_You can run unitary globally if preferred with `composer global require maplephp/unitary`._

---

### 2. Create a Test File

Create a file like `tests/unitary-request.php`. Unitary automatically scans all files prefixed with `unitary-` (excluding `vendor/`).

Paste this test boilerplate to get started:

```php
use MaplePHP\Unitary\{TestCase};

group("HTTP Request", function(TestCase $case) {

    $request = new Request("GET", "https://example.com/?id=1&slug=hello");

    $case->expect($request->getUri()->getQuery())
        ->hasQueryParam("id", 1)
        ->hasQueryParam("slug", "hello")
        ->validate();
});
```

> 💡 Tip: Run `php vendor/bin/unitary --template` to auto-generate this boilerplate code.

---

### 3. Run Tests

```bash
php vendor/bin/unitary
```

#### Need help?

```bash
php vendor/bin/unitary --help
```

#### The Output:
![Prompt demo](http://wazabii.se/github-assets/unitary/unitary-cli-state-pass.png)
*And that is it! Your tests have been successfully executed!*

With that, you are ready to create your own tests!

---

## 📅 Latest Release

**v2.0.0**
This release marks Unitary’s transition from a testing utility to a full framework. With the core in place, expect rapid improvements in upcoming versions.

---

## 🧱 Built From the Ground Up

Unitary stands on a solid foundation of years of groundwork. Before Unitary was possible, these independent components were developed:


* [`maplephp/Emitron`](https://github.com/maplephp/emitron) – PSR-15 kernel and middleware engine 
* [`maplephp/http`](https://github.com/maplephp/http) – PSR-7 HTTP messaging and stream handling
* [`maplephp/prompts`](https://github.com/maplephp/prompts) – Interactive prompt/command engine
* [`maplephp/blunder`](https://github.com/maplephp/blunder) – A pretty error handling framework
* [`maplephp/validate`](https://github.com/maplephp/validate) – Type-safe input validation
* [`maplephp/dto`](https://github.com/maplephp/dto) – Strong data transport
* [`maplephp/container`](https://github.com/maplephp/container) – PSR-11 Container, container and DI system
* [`maplephp/cache`](https://github.com/maplephp/cache) – PSR-6 and PSR-16 caching library

This full control means everything works together, no patching, no adapters and no guesswork.

---

## Philosophy

> **Test everything. All the time. Without friction.**

TDD becomes natural when your test suite runs in under a second, even with 100,000 cases. No more cherry-picking. No more skipping.

---

## Like The CLI Theme?
That’s DarkBark. Dark, quiet, confident, like a rainy-night synthwave playlist for your CLI.

[Download it here](https://github.com/MaplePHP/DarkBark)


---

## 🤝 Contribute

Unitary is still young — your bug reports, feedback, and suggestions are hugely appreciated.

If you like what you see, consider:

* Reporting issues
* Sharing feedback
* Submitting PRs
* Starring the repo ⭐

---

## 📬 Stay in Touch

Follow the full suite of MaplePHP tools:

* [https://github.com/MaplePHP](https://github.com/MaplePHP)
