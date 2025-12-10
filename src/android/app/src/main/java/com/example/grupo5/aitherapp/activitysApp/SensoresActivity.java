package com.example.grupo5.aitherapp.activitysApp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.getListaSensores;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.example.grupo5.aitherapp.pojos.PojoUsuario;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.io.Console;
import java.lang.reflect.Type;
import java.util.List;

public class SensoresActivity extends AppCompatActivity {

   private List<PojoSensor> listaSensores;
   private List<String> nombresSpinner;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_sensores_afiliados);

        SharedPreferences prefs = getSharedPreferences("SesionUsuario",MODE_PRIVATE);

        // Recuperamos el JSON almacenado
        String jsonLista = prefs.getString("ListaSensores", "[]");

        // Convertimos JSON -> List<PojoSensor>
        Gson gson = new Gson();
        Type tipoLista = new TypeToken<List<PojoSensor>>(){}.getType();
        listaSensores = gson.fromJson(jsonLista, tipoLista);

        for (int i = 0; i < listaSensores.size(); i++) {
            nombresSpinner.add("Sensor " + (i + 1));
        }

    }


}
