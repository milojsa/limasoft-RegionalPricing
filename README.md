# Regional pricing by postcode for Shopware 6

Regional prices and service areas resolved from the customer postcode, without breaking the
HTTP cache.

Shopware 6.6, PHP 8.2+, MIT.

## The problem

Shopware's rule builder evaluates a zip code against the active shipping address. A guest
browsing a category page has no address, so there is nothing to evaluate and no regional
price to show.

The obvious fix is to put the postcode into the sales channel context. That breaks the
reverse proxy. The postcode is not part of the cache key, so one zone's price gets served
to the whole country.

## The approach

1. Postcodes collapse into a small number of zones. Not one rule per postcode.
2. Each zone owns one rule builder rule. Prices and surcharges attach to the zone rule.
3. The zone is resolved once, then its rule id is merged into the context:

```php
$context->setRuleIds(array_merge(
    $context->getRuleIds(),
    $this->zoneResolver->ruleIdsFor($postcode)
));
```

The cache hash is already built from the active rule ids, in
`CacheResponseSubscriber::buildCacheHash()`. Injecting the zone rule changes the hash, so
two postcodes in different zones cannot serve each other a cached price. No custom cache
key work is needed.

Shopware can also fold named cookies into that hash. The postcode cookie is deliberately
left out of it, because that would split the cache per postcode instead of per zone. A few
zones means a few cache variants. Thousands of postcodes would mean thousands.

### Decorator order matters

`CachedSalesChannelContextFactory` caches the built context per sales channel, and its key
does not contain the postcode. So the zone injection has to happen outside that cache, not
inside it. Symfony applies the lowest `decoration-priority` last, which puts it outermost,
so this plugin uses `-2000` against the core factory's `-1000`.

Get this backwards and the first visitor's zone is cached and served to everyone. That is
the same bug as putting the postcode in the context, one layer down.

4. Excluded areas use the same mechanism. A postcode outside the service area resolves to a
   zone marked not serviceable, so availability needs no second system.

A zone rule must never match on its own. It is activated by injection only. `ZoneRule::match()`
therefore returns `false`, because a rule with an empty condition set would evaluate to true
and every zone price would apply to everyone.

## Zone matching

Three pattern forms per zone, any number of patterns:

| Pattern | Meaning |
| --- | --- |
| `10115` | exact postcode |
| `101*` | prefix |
| `10000-10999` | inclusive numeric range |

Zone priority decides first. Pattern precision only breaks a tie, where an exact match beats
a longer prefix, a longer prefix beats a shorter one, and any prefix beats a range. Patterns
are held per country ISO, so one install can serve more than one country.

The zone map is small by design, so it is loaded once and cached under the tag
`limasoft-price-zone`. Writing a zone or a postcode pattern invalidates it.

## What the team edits without a developer

Zones, their postcode patterns, priority and the serviceable flag are a custom entity, so
they are editable through the admin and the Admin API. Creating a zone creates its rule
automatically, so prices can be attached to it straight away.

Plugin configuration covers whether the regional price appears while browsing or only in the
cart and checkout, and whether the postcode field shows in the header.

## Accessibility

The postcode gate is a custom component, so no accessible theme default protects it. It
ships with a real `<label>`, `autocomplete="postal-code"`, help text wired through
`aria-describedby`, and an `aria-live` status region so a screen reader hears the zone
change rather than only seeing it.

All wording lives in snippet files, de-DE and en-GB, editable in the admin. No strings are
hardcoded in templates.

The postcode cookie is technically required and carries no tracking data, and it is still
declared in the cookie consent list.

## Install

```bash
bin/console plugin:refresh
bin/console plugin:install --activate LimasoftRegionalPricing
bin/console cache:clear
```

## Tests

```bash
composer install
vendor/bin/phpunit
```

The unit suite covers postcode normalisation and zone matching, which is where the edge
cases live. `tests/Integration/ZoneCacheSeparationTest.php` covers the part that matters
most: it warms the context cache with one postcode, then builds a context for a second
postcode in a different zone, and asserts neither carries the other's zone rule. It uses
the cookie path deliberately, because passing the postcode as a context option would land
it in the context cache key and hide the very bug the test exists to catch.

Verified on a real Shopware 6.6.10.29 install: three zones resolve to three distinct
rules, each postcode yields only its own zone rule, unknown and malformed postcodes yield
none, and the rule ids differ so the cache hashes differ.

## Scope

This is a working core, not a full regional pricing suite. Deliberately left out:

- Zone names are internal admin labels and are not translated. A customer facing zone
  selector would need a translated field.
- Placement surcharges, lead times, service days and the holiday calendar follow the same
  custom entity pattern and are separate modules.
- The admin module UI is not included yet. Zones are managed through the Admin API until it
  lands. The zone condition itself renders in the rule builder already, through
  `ZoneRule::getConfig()`, so no custom condition component is needed.
