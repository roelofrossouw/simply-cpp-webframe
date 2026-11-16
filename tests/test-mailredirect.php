<?php

  // mail.redirect and mail.redirect_except: where BS\Email sends
  require __DIR__ . '/check.inc';

  Config::Load();
  Config::Set('mail.redirect', null);
  Same('Name <name@example.com>', BS\Email::Recipient('Name <name@example.com>'), 'no redirect: the recipient');

  Config::Set('mail.redirect', 'dev@example.org');
  Same('dev@example.org', BS\Email::Recipient('Name <name@example.com>'), 'redirect without exceptions: everything redirected');

  Config::Set('mail.redirect_except', ['example.com', 'Someone@Example.NET']);
  Same('Name <name@example.com>', BS\Email::Recipient('Name <name@example.com>'), 'a listed domain gets its own email');
  Same('NAME@EXAMPLE.COM', BS\Email::Recipient('NAME@EXAMPLE.COM'), 'domains match in any case');
  Same('dev@example.org', BS\Email::Recipient('name@sub.example.com'), 'a subdomain is not the domain');
  Same('dev@example.org', BS\Email::Recipient('name@badexample.com'), 'a domain that only ends the same is not the domain');
  Same('dev@example.org', BS\Email::Recipient('example.com@other.example.org'), 'only the part after the last @ is the domain');
  Same('someone@example.net', BS\Email::Recipient('someone@example.net'), 'a listed address, any case');
  Same('dev@example.org', BS\Email::Recipient('other@example.net'), 'another address at that domain is redirected');
  Same('dev@example.org', BS\Email::Recipient('not an address'), 'no address at all: redirected');

  Config::Set('mail.redirect_except', ' example.com , someone@example.net ');
  Same('Name <name@example.com>', BS\Email::Recipient('Name <name@example.com>'), 'one comma-separated string');
  Same('someone@example.net', BS\Email::Recipient('someone@example.net'), 'an address in the string');
  Same('dev@example.org', BS\Email::Recipient('x@example.org.example.com.evil'), 'nothing else matches');

  Config::Set('mail.redirect_except', ['', 42, null]);
  Same('dev@example.org', BS\Email::Recipient('name@example.com'), 'empty or odd entries match nothing');

  Config::Set('mail.redirect', '');
  Same('name@example.com', BS\Email::Recipient('name@example.com'), 'an empty redirect is off');
