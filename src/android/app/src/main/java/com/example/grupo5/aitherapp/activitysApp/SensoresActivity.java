package com.example.grupo5.aitherapp.activitysApp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.getListaSensores;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.pojos.PojoUsuario;

import java.io.Console;

public class SensoresActivity extends AppCompatActivity {

    private PojoUsuario usuario = new PojoUsuario();
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_sensores_afiliados);

        SharedPreferences prefs = getSharedPreferences("SesionUsuario",MODE_PRIVATE);
        usuario.setId(prefs.getString("id",""));
        usuario.setAction("getObtenerSensoresUsuario");
        getListaSensores(usuario,this);
    }
}
