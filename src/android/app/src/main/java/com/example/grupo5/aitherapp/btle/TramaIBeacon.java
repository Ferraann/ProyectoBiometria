package com.example.grupo5.aitherapp.btle;
import java.util.Arrays;

// -----------------------------------------------------------------------------------
// @author: Jordi Bataller i Mascarell
// -----------------------------------------------------------------------------------
public class TramaIBeacon {
    private byte[] prefijo = null; // 9 bytes
        private byte[] uuid = null; // 16 bytes
        private byte[] major = null; // 2 bytes
        private byte[] minor = null; // 2 bytes
        private byte txPower = 0; // 1 byte

        private byte[] losBytes;

        private byte[] advFlags = null; // 3 bytes
        private byte[] advHeader = null; // 2 bytes
        private byte[] companyID = new byte[2]; // 2 bytes
        private byte iBeaconType = 0 ; // 1 byte
        private byte iBeaconLength = 0 ; // 1 byte

        // -------------------------------------------------------------------------------
        // Obtener el prefijo
        // -------------------------------------------------------------------------------
        public byte[] getPrefijo() {
            return prefijo;
        }

        // -------------------------------------------------------------------------------
        //  Obtener el UUID
        // -------------------------------------------------------------------------------
        public byte[] getUUID() {
            return uuid;
        }

        // -------------------------------------------------------------------------------
        // Obtener el major
        // -------------------------------------------------------------------------------
        public byte[] getMajor() {
            return major;
        }

        // -------------------------------------------------------------------------------
        // Obtener el minor
        // -------------------------------------------------------------------------------
        public byte[] getMinor() {
            return minor;
        }

        // -------------------------------------------------------------------------------
        // Obtener el TxPower
        // -------------------------------------------------------------------------------
        public byte getTxPower() {
            return txPower;
        }

        // -------------------------------------------------------------------------------
        // Obtener el LosBytes
        // -------------------------------------------------------------------------------
        public byte[] getLosBytes() {
            return losBytes;
        }

        // -------------------------------------------------------------------------------
        // Obtener el AdvFlags
        // -------------------------------------------------------------------------------
        public byte[] getAdvFlags() {
            return advFlags;
        }

        // -------------------------------------------------------------------------------
        // Obtener el AdvHeader
        // -------------------------------------------------------------------------------
        public byte[] getAdvHeader() {
            return advHeader;
        }

        // -------------------------------------------------------------------------------
        // Obtener el id de la compañia
        // -------------------------------------------------------------------------------
        public byte[] getCompanyID() {
            return companyID;
        }

        // -------------------------------------------------------------------------------
        // Obtener el tipo del Beacon
        // -------------------------------------------------------------------------------
        public byte getiBeaconType() {
            return iBeaconType;
        }

        // -------------------------------------------------------------------------------
        // Obtener la longitud del beacon
        // -------------------------------------------------------------------------------
        public byte getiBeaconLength() {
            return iBeaconLength;
        }

        // -------------------------------------------------------------------------------
        // Obtener los datos para montar la trama
        // -------------------------------------------------------------------------------
        public TramaIBeacon(byte[] bytes ) {
            this.losBytes = bytes;

            prefijo = Arrays.copyOfRange(losBytes, 0, 8+1 ); // 9 bytes
            uuid = Arrays.copyOfRange(losBytes, 9, 24+1 ); // 16 bytes
            major = Arrays.copyOfRange(losBytes, 25, 26+1 ); // 2 bytes
            minor = Arrays.copyOfRange(losBytes, 27, 28+1 ); // 2 bytes
            txPower = losBytes[ 29 ]; // 1 byte

            advFlags = Arrays.copyOfRange( prefijo, 0, 2+1 ); // 3 bytes
            advHeader = Arrays.copyOfRange( prefijo, 3, 4+1 ); // 2 bytes
            companyID = Arrays.copyOfRange( prefijo, 5, 6+1 ); // 2 bytes
            iBeaconType = prefijo[ 7 ]; // 1 byte
            iBeaconLength = prefijo[ 8 ]; // 1 byte

        } // ()
    } // class
// -----------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------

