# Contributing to Phalcon Kit

Phalcon Kit is an open source project and a volunteer effort. Phalcon Kit welcomes contribution from everyone.
Please take a moment to review this document in order to make the contribution process easy and effective for everyone
involved.

Following these guidelines helps to communicate that you respect the time of the developers managing and developing this
open source project. In return, they should reciprocate that respect in addressing your issue or assessing patches and
features.

## Contributions

Contributions to Phalcon Kit should be made in the form of GitHub pull requests.
Each pull request will be reviewed by a core contributor (someone with permission to land patches) and either landed in
the main tree or given feedback for changes that would be required before it can be merged. All contributions should
follow this format, even those from core contributors.

## Questions & Support

We use the GitHub issues for tracking bugs and feature requests and have limited bandwidth to address all of them. Thus
we only accept bug reports, new feature requests and pull requests in GitHub. Our great community and contributors are
happy to help you though! Please use these community resources for obtaining help.

_Please use the [Documentation](https://github.com/phalcon-kit/docs) before anything else. You can also use the search feature
in our documents to find what you are looking for. If your question is still not answered, there are more options below._

* Questions should go to [Github Discussions](https://github.com/orgs/phalcon-kit/discussions)
* Another way is to ask a question on [Stack Overflow](https://stackoverflow.com/) and tag it with
  [`phalcon-kit`](https://stackoverflow.com/questions/tagged/phalcon-kit)
* If you still believe that what you found is a bug, please
  [open an issue](https://github.com/phalcon-kit/core/issues/new)

Please report bugs when you've exhausted all of the above options.

## Bug Report Checklist

* Do not report security vulnerabilities in public issues. Use GitHub private vulnerability reporting from the
  repository Security tab instead.
* Make sure you are using the latest released version of Phalcon Kit before submitting a bug report.
  Bugs in versions older than the latest released one will not be addressed by the core team
* If you have found a bug it is important to add relevant reproducibility information to your issue to allow us 
  to reproduce the bug and fix it quicker. Add a script, small program or repository providing the necessary code to 
  make everyone reproduce the issue reported easily. If a bug cannot be reproduced by the development it would be
  difficult provide corrections and solutions.
* Be sure that information such as OS, Phalcon Kit version, and PHP version are part of the bug report

## Pull Request Checklist

* Don't submit your pull requests to the `master` branch. Branch from the required branch and,
  if needed, rebase to the proper branch before submitting your pull request.
  If it doesn't merge cleanly with master you may be asked to rebase your changes
* Don't put submodule updates in your pull request unless they are to landed commits
* Add tests relevant to the fixed bug or new feature.
* Run `composer qa` before opening the pull request.
* Update documentation or skills when changing public behavior, generated output, or maintainer workflow.

## Requesting Features

If you have a change or new feature in mind, please fill an NFR on the GitHub.

### New Feature Request (NFR)

A NFR is a short document explaining how a new feature request must be submitted, how it can be implemented, and how it can help core developers and others to understand implement it.

A NFR contains:
* Suggested syntax
* Suggested class names and methods
* A short documentation
* If the feature is already implemented in other frameworks, a short explanation of how that was implemented and its advantages

In the following cases a new feature request will be rejected:
* The feature makes the framework slow
* The feature doesn't provide any additional value to the framework
* The NFR is not clear, bad documentation, unclear explanation, etc.
* The NFR doesn't follow the current guidelines/philosophy of the framework
* The NFR affects/breaks applications developed in current/older versions of the framework
* The original poster doesn't provide feedback/input when requested
* It's technically impossible to implement
* It can only be used in the development/testing stages
* Submitted/proposed classes/components don't follow the [Single Responsibility Principle][SRP]
* Static methods aren't allowed

To send a NFR you don't need to provide PHP, Zephir, or C code or develop the feature. New Feture requests explain the goal of the intended implementation and open discussion on how best to implement it.

All NFRs should be posted as a new issue on [Github][github-issues].

[SRP]: http://en.wikipedia.org/wiki/Single_responsibility_principle
[github-issues]: https://github.com/phalcon-kit/core/issues
