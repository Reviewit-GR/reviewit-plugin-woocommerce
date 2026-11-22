# Reviewit plugin for WooCommerce

Invites a shop's customers to review it on [Reviewit.gr](https://www.reviewit.gr) when a WooCommerce order is completed. It calls
`POST /v1/invitations` ([API reference](https://help.reviewit.gr/api/invitations/)) with the customer's email, first name and the order number,
and nothing else. The description for shop owners is in [readme.txt](readme.txt), the format the WordPress.org plugin directory reads.

## Install

Download `reviewit-invitations-<version>.zip` from the [releases](../../releases), then in WordPress: Plugins > Add New > Upload Plugin >
activate. Open WooCommerce > Reviewit and paste the key from your business page on Reviewit («Σύνδεση καταστήματος»). Start with the test key
(`rvk_test_...`): complete an order and read the note on it.

## How it works

On `woocommerce_order_status_completed` it queues one Action Scheduler job per order, which makes the call and writes the answer as a private
order note. Reviewit applies the rules (delay, nobody twice in 90 days, unsubscribed, already reviewed, monthly limit), so the plugin has none.
A failure of Reviewit or the network is tried again, up to three times an hour apart; every other answer is final. An order is answered once.

- `reviewit-invitations.php`: the hooks. `includes/Sender.php`: the job. `includes/Client.php`: the call and how its answer is read.
  `includes/Settings.php`: the page with the key.
- For developers: the filter `reviewit_invitation_should_send` leaves an order out; `define( 'REVIEWIT_API_URL', 'http://127.0.0.1:8090' );` in
  `wp-config.php` calls another API (a local one).

## Develop

```
php tests/run.php            # no WordPress and no packages needed
python3 bin/compile-mo.py    # after editing languages/*.po (WordPress reads only .mo)
bin/build-zip.sh             # dist/reviewit-invitations-<version>.zip; the version is in the plugin header, its constant and readme.txt
```

## Release

Push a tag equal to the version (`git tag v0.1.0 && git push --tags`): `release.yml` makes a GitHub release with the zip, and, once the plugin is
approved on WordPress.org, `deploy-wporg.yml` publishes it there (secrets `SVN_USERNAME` and `SVN_PASSWORD` in an environment named
`wordpress-org`). The plugin page's icon and banner are in `.wordpress-org/`.

## License

GPL-2.0-or-later, as WordPress requires ([LICENSE](LICENSE)).
