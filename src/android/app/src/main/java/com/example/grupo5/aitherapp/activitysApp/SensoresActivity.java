package com.example.grupo5.aitherapp.activitysApp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.getListaSensores;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.Spinner;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.example.grupo5.aitherapp.pojos.PojoUsuario;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.io.Console;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class SensoresActivity extends AppCompatActivity {

    private List<PojoSensor> listaSensores;
    private List<String> nombresSpinner = new ArrayList<>();;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_sensores_afiliados);

        // 1. Recuperar lista de sensores guardada
        SharedPreferences prefs = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        String jsonLista = prefs.getString("ListaSensores", "[]");

        Gson gson = new Gson();
        Type tipoLista = new TypeToken<List<PojoSensor>>(){}.getType();
        listaSensores = gson.fromJson(jsonLista, tipoLista);

        // 2. Crear lista de nombres para el Spinner
        for (int i = 0; i < listaSensores.size(); i++) {
            nombresSpinner.add("Sensor " + (i + 1));
        }

        // 3. Vincular Spinner del layout
        Spinner spinner = findViewById(R.id.listaDeSensores);

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

                PojoSensor sensorSeleccionado = listaSensores.get(position);

                Log.d("SensoresActivity", "Seleccionado: ID=" + sensorSeleccionado.getMac()
                        + " MAC=" + sensorSeleccionado.getMac());

                Toast.makeText(SensoresActivity.this,
                        "Seleccionado " + nombresSpinner.get(position),
                        Toast.LENGTH_SHORT).show();
            }

            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }


    public void buscarSensor(){

    }
}
