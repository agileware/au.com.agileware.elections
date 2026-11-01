# Security Policy

At [Agileware](https://agileware.com.au), we take the security of our open-source software seriously. Elections handles nominations, ballots and results, so we treat problems with who can vote, how votes are counted and the secrecy of ballots as security issues. If you believe you have found a vulnerability in this repository, we appreciate your help in disclosing it to us responsibly.

## Supported Versions

Security fixes are made for the latest release of the current major version. Please make sure you are running an updated release before submitting a report.

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | :white_check_mark: |
| 1.x     | :x:                |

Version 1.x is no longer supported and will not receive security patches. Sites on 1.x should upgrade to 2.x, which requires CiviCRM 6.16 or later.

*Note: Security patches are released as a new tagged version on GitHub.*

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues, pull requests, or community forums.**

To report a vulnerability, please follow these steps:

1. **Email Us:** Send an email directly to **[security@agileware.com.au](mailto:security@agileware.com.au)**, or use GitHub's private vulnerability reporting from the Security tab of this repository.
2. **Provide Details:** Include as much detail as possible to help us reproduce and address the issue, such as:
   - A detailed description of the vulnerability.
   - Steps to reproduce or proof-of-concept code or screenshots.
   - The version of Elections, CiviCRM, the CMS (WordPress, Drupal or Joomla) and PHP affected.
   - The potential impact of the issue if exploited, for example whether ballots, results or voter identity are affected.
   - Please do not include real voter or ballot data.

For general inquiries or non-security support, please visit [agileware.com.au/contact](https://agileware.com.au/contact).

### What to Expect

* **Acknowledgement:** We aim to acknowledge receipt of your report within **2 business days**.
* **Assessment & Updates:** Our team will investigate the report to confirm the vulnerability. We will keep you updated on our progress toward a fix at least once every **5 business days**.
* **Resolution:** If accepted, we will develop a patch and issue a new release. We will notify you once the fix has been published.
* **Public Disclosure:** We request that you maintain confidentiality until we have issued an update to protect affected sites. Once resolved, we are happy to acknowledge your contribution in our release notes (unless you prefer to remain anonymous).

Thank you for helping keep Agileware products and the CiviCRM community safe!
