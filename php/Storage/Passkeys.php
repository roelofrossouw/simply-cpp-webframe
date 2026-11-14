<?php

  namespace Storage;

  /**
   * Passkeys (Auth\Passkey), for a storage that offers them: a Backend that also implements this. Auth\Passkey is only
   * offered when the storage in use does.
   *
   * A passkey belongs to an account of any provider and signs it in as itself. Ids and keys are base64url text, as
   * SC\Passkey gives them; values as Backend's are.
   */
  interface Passkeys
  {
    /** The user's passkeys, oldest first: id, credential_id, transports (an array), name, created, last_used (or null) */
    public function Passkeys(int $UserID): array;

    /** A passkey by its credential id: id, user_id, public_key, sign_count; null when there's none */
    public function Passkey(string $CredentialID): ?array;

    /**
     * Keeps a new passkey for the user
     *
     * @param array $Credential What SC\Passkey::verifyRegistration() gave: id, publicKey, signCount, transports, backedUp, ...
     * @return string|null Why it wasn't kept, or null
     */
    public function AddPasskey(int $UserID, array $Credential, string $Name): ?string;

    /** A passkey signed in: its new sign count, whether it's backed up now, and when */
    public function PasskeyUsed(int $ID, int $SignCount, bool $BackedUp): void;

    /** Removes one of the user's own passkeys */
    public function RemovePasskey(int $UserID, int $ID): ?string;

    /**
     * Records a sign-in by the user's id (a passkey's, whatever provider the account has)
     *
     * @return array|null user_id, provider, email, first_name, last_name, access, as Backend::SignIn() gives them; null when
     *   there's no such user
     */
    public function UserSignIn(int $UserID): ?array;
  }
