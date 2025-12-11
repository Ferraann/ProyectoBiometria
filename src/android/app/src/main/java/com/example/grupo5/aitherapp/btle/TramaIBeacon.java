package com.example.grupo5.aitherapp.btle;
import java.util.Arrays;

/**
 * @brief Clase que representa y descompone una trama iBeacon a partir de un array de bytes.
 *
 * Permite obtener el prefijo, UUID, major, minor, TxPower y demás campos de la trama
 * publicitaria recibida por BLE.
 *
 * @author Jordi Bataller
 */
public class TramaIBeacon {

    private byte[] prefijo = null;      ///< 9 bytes iniciales de la trama iBeacon
    private byte[] uuid = null;         ///< UUID del beacon (16 bytes)
    private byte[] major = null;        ///< Valor "major" (2 bytes)
    private byte[] minor = null;        ///< Valor "minor" (2 bytes)
    private byte   txPower = 0;         ///< Potencia TX calibrada (1 byte)

    private byte[] losBytes;            ///< Trama completa recibida

    private byte[] advFlags = null;     ///< Flags de advertising (3 bytes)
    private byte[] advHeader = null;    ///< Cabecera de advertising (2 bytes)
    private byte[] companyID = new byte[2]; ///< Identificador de fabricante (2 bytes)
    private byte   iBeaconType = 0;     ///< Tipo de iBeacon (1 byte)
    private byte   iBeaconLength = 0;   ///< Longitud del frame iBeacon (1 byte)

    /**
     * @brief Obtiene el prefijo del paquete iBeacon.
     * @return Array de 9 bytes correspondientes al prefijo.
     */
    public byte[] getPrefijo() {
        return prefijo;
    }

    /**
     * @brief Obtiene el UUID del beacon.
     * @return Array de 16 bytes correspondientes al UUID.
     */
    public byte[] getUUID() {
        return uuid;
    }

    /**
     * @brief Obtiene el valor Major.
     * @return Array de 2 bytes del Major.
     */
    public byte[] getMajor() {
        return major;
    }

    /**
     * @brief Obtiene el valor Minor.
     * @return Array de 2 bytes del Minor.
     */
    public byte[] getMinor() {
        return minor;
    }

    /**
     * @brief Devuelve la potencia TX del beacon.
     * @return Byte que representa el TxPower.
     */
    public byte getTxPower() {
        return txPower;
    }

    /**
     * @brief Obtiene la trama completa recibida.
     * @return Array con todos los bytes de la trama.
     */
    public byte[] getLosBytes() {
        return losBytes;
    }

    /**
     * @brief Obtiene los flags de advertising.
     * @return Array de 3 bytes con los flags.
     */
    public byte[] getAdvFlags() {
        return advFlags;
    }

    /**
     * @brief Obtiene la cabecera de advertising.
     * @return Array de 2 bytes con la cabecera.
     */
    public byte[] getAdvHeader() {
        return advHeader;
    }

    /**
     * @brief Obtiene el identificador de la compañía (Apple: 0x004C).
     * @return Array de 2 bytes con el Company ID.
     */
    public byte[] getCompanyID() {
        return companyID;
    }

    /**
     * @brief Devuelve el tipo de iBeacon.
     * @return Byte que indica el tipo de frame.
     */
    public byte getiBeaconType() {
        return iBeaconType;
    }

    /**
     * @brief Devuelve la longitud de la estructura iBeacon.
     * @return Byte con la longitud.
     */
    public byte getiBeaconLength() {
        return iBeaconLength;
    }

    /**
     * @brief Constructor que interpreta una trama iBeacon y separa sus campos.
     *
     * Extrae automáticamente el prefijo, UUID, major, minor, TxPower y otros
     * parámetros necesarios para identificar correctamente un iBeacon.
     *
     * @param bytes Array completo de la trama BLE recibida (mínimo 30 bytes).
     */
    public TramaIBeacon(byte[] bytes ) {
        this.losBytes = bytes;

        prefijo = Arrays.copyOfRange(losBytes, 0, 8+1 );  // 9 bytes
        uuid    = Arrays.copyOfRange(losBytes, 9, 24+1 ); // 16 bytes
        major   = Arrays.copyOfRange(losBytes, 25, 26+1 ); // 2 bytes
        minor   = Arrays.copyOfRange(losBytes, 27, 28+1 ); // 2 bytes
        txPower = losBytes[29]; // 1 byte

        advFlags      = Arrays.copyOfRange(prefijo, 0, 2+1); // 3 bytes
        advHeader     = Arrays.copyOfRange(prefijo, 3, 4+1); // 2 bytes
        companyID     = Arrays.copyOfRange(prefijo, 5, 6+1); // 2 bytes
        iBeaconType   = prefijo[7]; // 1 byte
        iBeaconLength = prefijo[8]; // 1 byte
    }
}
