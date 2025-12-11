package com.example.grupo5.aitherapp.btle;

import android.Manifest;
import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.le.BluetoothLeScanner;
import android.bluetooth.le.ScanCallback;
import android.bluetooth.le.ScanFilter;
import android.bluetooth.le.ScanResult;
import android.bluetooth.le.ScanSettings;
import android.content.Context;
import android.content.pm.PackageManager;
import android.util.Log;

import androidx.core.app.ActivityCompat;

import java.util.ArrayList;
import java.util.List;

public class BtleScannerMultiple {
    private static final String TAG = "MULTI_BLE_SCANNER";

    private final Context context;
    private final List<String> macsABuscar;
    private final Listener listener;

    private BluetoothLeScanner scanner;
    private ScanCallback callback;

    /**
     * Interfaz de comunicación con quien use este escáner.
     * Cada vez que se detecta un sensor afiliado, se llamará a onSensorDetectado()
     */
    public interface Listener {
        /**
         * @param mac       MAC del sensor detectado
         * @param rssi      Intensidad de señal recibida
         * @param distancia Distancia aproximada en metros
         */
        void onSensorDetectado(String mac, int rssi, double distancia);
    }

    /**
     * Constructor del escáner.
     *
     * @param context     Contexto Android (Activity o Application)
     * @param macsABuscar Lista de direcciones MAC de sensores afiliados al usuario
     * @param listener    Callback para recibir eventos de detección
     */
    public BtleScannerMultiple(Context context, List<String> macsABuscar, Listener listener) {
        this.context = context.getApplicationContext(); // guardamos el application context
        this.macsABuscar = macsABuscar;
        this.listener = listener;

        BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
        if (adapter != null) {
            this.scanner = adapter.getBluetoothLeScanner();
        } else {
            Log.e(TAG, "BluetoothAdapter es null: ¿el dispositivo tiene Bluetooth?");
        }
    }

    /**
     * Inicia el escaneo BLE filtrando solo los dispositivos cuyas MAC
     * están en la lista macsABuscar.
     */
    public void iniciarEscaneo() {

        if (scanner == null) {
            Log.e(TAG, "No se ha podido obtener el BluetoothLeScanner");
            return;
        }

        if (macsABuscar == null || macsABuscar.isEmpty()) {
            Log.w(TAG, "No hay MACs configuradas para buscar");
            return;
        }

        // 1) Creamos un filtro por cada MAC afiliada
        List<ScanFilter> filtros = new ArrayList<>();
        for (String mac : macsABuscar) {
            if (mac == null || mac.trim().isEmpty()) continue;

            ScanFilter filtro = new ScanFilter.Builder()
                    .setDeviceAddress(mac)
                    .build();
            filtros.add(filtro);
        }

        if (filtros.isEmpty()) {
            Log.w(TAG, "No se han podido crear filtros de escaneo (MACs vacías)");
            return;
        }

        // 2) Configuramos el escaneo: modo alta frecuencia (más rápido, más consumo)
        ScanSettings settings = new ScanSettings.Builder()
                .setScanMode(ScanSettings.SCAN_MODE_LOW_LATENCY)
                .build();

        // 3) Definimos el callback que recibirá los resultados
        callback = new ScanCallback() {
            @Override
            public void onScanResult(int callbackType, ScanResult result) {

                BluetoothDevice device = result.getDevice();
                if (device == null) return;

                String macDetectada = device.getAddress();
                int rssi = result.getRssi();
                double distancia = calcularDistancia(rssi);

                Log.d(TAG, "Detectado sensor afiliado -> MAC=" + macDetectada +
                        " RSSI=" + rssi +
                        " distancia=" + distancia + " m");

                if (listener != null) {
                    listener.onSensorDetectado(macDetectada, rssi, distancia);
                }
            }

            @Override
            public void onScanFailed(int errorCode) {
                Log.e(TAG, "Error en escaneo BLE, código=" + errorCode);
            }
        };

        // 4) Comprobamos permisos en tiempo de ejecución
        if (ActivityCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_SCAN)
                != PackageManager.PERMISSION_GRANTED) {
            Log.e(TAG, "Falta permiso BLUETOOTH_SCAN en tiempo de ejecución");
            return;
        }

        // 5) Arrancamos el escaneo con filtros y configuración
        scanner.startScan(filtros, settings, callback);
        Log.d(TAG, "Escaneo BLE iniciado para " + filtros.size() + " sensores afiliados");
    }

    /**
     * Detiene el escaneo si está en curso.
     * Debe llamarse, por ejemplo, al cerrar sesión o al cerrar la app.
     */
    public void detenerEscaneo() {
        if (scanner != null && callback != null) {

            if (ActivityCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_SCAN)
                    != PackageManager.PERMISSION_GRANTED) {
                Log.e(TAG, "Falta permiso BLUETOOTH_SCAN para detener escaneo");
                return;
            }

            scanner.stopScan(callback);
            callback = null;

            Log.d(TAG, "Escaneo BLE detenido");
        }
    }

    /**
     * Convierte un valor RSSI en una distancia aproximada en metros.
     * Es una estimación (aprox), no una medida exacta.
     *
     * @param rssi Intensidad de la señal (negativa, por ejemplo -60)
     * @return distancia en metros aproximada
     */
    private double calcularDistancia(int rssi) {
        int txPower = -59; // Potencia de emisión calibrada (valor típico si no se conoce)
        // Fórmula logarítmica típica para BLE
        return Math.pow(10d, ((double) txPower - rssi) / 20.0);
    }
}
