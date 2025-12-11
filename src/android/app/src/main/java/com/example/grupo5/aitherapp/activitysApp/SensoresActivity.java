package com.example.grupo5.aitherapp.activitysApp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.getListaSensores;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.btle.BtleScannerMultiple;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class SensoresActivity extends AppCompatActivity {

    private List<PojoSensor> listaSensores;
    private List<String> nombresSpinner = new ArrayList<>();

    private Spinner spinner;
    private TextView infoSensor;
    private TextView dondeEstaSensor;

    private PojoSensor sensorSeleccionado;
    private BtleScannerMultiple bleScanner;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_sensores_afiliados);

        // Referencias a vistas
        spinner = findViewById(R.id.listaDeSensores);
        infoSensor = findViewById(R.id.infoSensor);
        dondeEstaSensor = findViewById(R.id.dondeEstaSensor);

        // 1. Recuperar lista de sensores guardada
        SharedPreferences prefs = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        String jsonLista = prefs.getString("ListaSensores", "[]");

        Gson gson = new Gson();
        Type tipoLista = new TypeToken<List<PojoSensor>>(){}.getType();
        listaSensores = gson.fromJson(jsonLista, tipoLista);

        if (listaSensores == null) {
            listaSensores = new ArrayList<>();
        }

        // 2. Crear lista de nombres para el Spinner
        for (int i = 0; i < listaSensores.size(); i++) {
            nombresSpinner.add("Sensor " + (i + 1));
        }

        // 3. Vincular Spinner del layout
        ArrayAdapter<String> adapter = new ArrayAdapter<>(
                this,
                android.R.layout.simple_spinner_item,
                nombresSpinner
        );
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinner.setAdapter(adapter);

        // 4. Listener para saber qué sensor selecciona el usuario
        spinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {

                if (listaSensores.isEmpty()) return;

                sensorSeleccionado = listaSensores.get(position);

                Log.d("SensoresActivity", "Seleccionado: MAC=" + sensorSeleccionado.getMac());

                infoSensor.setText(
                        "Sensor seleccionado:\n" +
                                "MAC: " + sensorSeleccionado.getMac()
                );

                Toast.makeText(SensoresActivity.this,
                        "Seleccionado " + nombresSpinner.get(position),
                        Toast.LENGTH_SHORT).show();
            }

            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }

    /**
     * Pulsar botón "buscarSensor" → empezar a escanear
     * y mostrar la distancia en el TextView dondeEstaSensor.
     */
    public void buscarSensor(View v){

        if (sensorSeleccionado == null) {
            Toast.makeText(this, "Primero selecciona un sensor", Toast.LENGTH_SHORT).show();
            return;
        }

        // Si ya había un escaneo activo en esta activity, lo detenemos
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
            bleScanner = null;
        }

        List<String> macs = new ArrayList<>();
        macs.add(sensorSeleccionado.getMac());

        bleScanner = new BtleScannerMultiple(this, macs, new BtleScannerMultiple.Listener() {
            @Override
            public void onSensorDetectado(String mac, int rssi, double distanciaAprox) {

                runOnUiThread(() -> {
                    String texto = String.format(
                            "Distancia aproximada al sensor:\n%.2f metros (RSSI: %d)",
                            distanciaAprox, rssi
                    );
                    dondeEstaSensor.setText(texto);
                });
            }

            @Override
            public void onSensorDesconectado(String mac) {
                runOnUiThread(() -> {
                    dondeEstaSensor.setText("El sensor " + mac + " se ha desconectado.");
                });
            }
        });

        bleScanner.iniciarEscaneo();

        Toast.makeText(this, "Buscando el sensor seleccionado...", Toast.LENGTH_SHORT).show();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();

        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
        }
    }
}
