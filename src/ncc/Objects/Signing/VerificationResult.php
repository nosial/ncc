<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
 *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
 *  of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 *
 */

    namespace ncc\Objects\Signing;

    use ncc\Enums\SignatureStatus;
    use ncc\Objects\Package\PackageSignature;

    class VerificationResult
    {
        private SignatureStatus $status;
        private ?string $message;
        /** @var array<int, array{signature: PackageSignature, status: SignatureStatus, message: string}> */
        private array $entries;

        /**
         * Public constructor
         *
         * @param SignatureStatus $status The overall status of the package
         * @param string|null $message A message describing the overall status when it is not described by an entry
         * @param array $entries The verification status of each signature
         */
        public function __construct(SignatureStatus $status, ?string $message=null, array $entries=[])
        {
            $this->status = $status;
            $this->message = $message;
            $this->entries = $entries;
        }

        /**
         * Creates a result from the verification status of each signature. Any invalid signature makes the package
         * invalid, then any key mismatch, otherwise a single trusted signature makes the package verified.
         *
         * @param array $entries The verification status of each signature
         * @return VerificationResult The combined result
         */
        public static function fromEntries(array $entries): VerificationResult
        {
            $statuses = array_map(fn(array $entry) => $entry['status'], $entries);
            foreach([SignatureStatus::INVALID, SignatureStatus::KEY_MISMATCH, SignatureStatus::VERIFIED, SignatureStatus::UNTRUSTED] as $status)
            {
                if(in_array($status, $statuses, true))
                {
                    return new VerificationResult($status, null, $entries);
                }
            }

            return new VerificationResult(SignatureStatus::UNSUPPORTED, 'None of the signatures of the package can be verified by this version of ncc', $entries);
        }

        /**
         * Retrieves the current signature status of the package.
         *
         * @return SignatureStatus The signature status of the package
         */
        public function getStatus(): SignatureStatus
        {
            return $this->status;
        }

        /**
         * Retrieves the message associated with the current instance.
         *
         * @return string|null The message if available, or null if no message is set.
         */
        public function getMessage(): ?string
        {
            return $this->message;
        }

        /**
         * Retrieves all entries stored in the current object.
         *
         * @return array The list of entries.
         */
        public function getEntries(): array
        {
            return $this->entries;
        }

        /**
         * Returns the entries with the given status
         *
         * @param SignatureStatus $status The status to filter by
         * @return array<int, array{signature: PackageSignature, status: SignatureStatus, message: string}>
         */
        public function getEntriesWithStatus(SignatureStatus $status): array
        {
            return array_values(array_filter($this->entries, fn(array $entry) => $entry['status'] === $status));
        }

        /**
         * Returns whether the result means the package must not be installed without an explicit override
         *
         * @return bool True if the package is invalid or signed with a key that does not match a pinned key
         */
        public function isFailure(): bool
        {
            return $this->status === SignatureStatus::INVALID || $this->status === SignatureStatus::KEY_MISMATCH;
        }

        /**
         * Returns a one-line summary of the result, using the first entry with the overall status
         *
         * @return string The summary
         */
        public function getSummary(): string
        {
            $entries = $this->getEntriesWithStatus($this->status);
            if(count($entries) > 0)
            {
                return $entries[0]['message'];
            }

            return $this->message ?? $this->status->value;
        }
    }
