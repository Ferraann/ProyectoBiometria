package com.example.grupo5.aitherapp.activitysApp;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.grupo5.aitherapp.PremioAdapter;
import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.pojos.PojoPremio;

import java.util.ArrayList;
import java.util.List;

public class ListaPremiosActivity extends AppCompatActivity {

    private RecyclerView recyclerView;
    private PremioAdapter adapter;
    private List<PojoPremio> premios;
    private TextView tvCoins;
    private ProgressBar progressBar;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_lista_premios);

        SharedPreferences prefs = getSharedPreferences("MiAppPrefs", MODE_PRIVATE);

        ImageView backArrow = findViewById(R.id.imgBackArrow);
        backArrow.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                finish();
            }
        });

        // Sumar 10 coins cada vez que se entra
        int coinsUsuario = prefs.getInt("coinsUsuario", 0); // 0 si no existe

        // Actualizar UI
        tvCoins = findViewById(R.id.coinNumber);
        progressBar = findViewById(R.id.progressBar);
        tvCoins.setText(String.valueOf(coinsUsuario));
        progressBar.setMax(50); // meta
        progressBar.setProgress(coinsUsuario);
        if (!prefs.contains("emailUsuario")) {
            prefs.edit().putString("emailUsuario", "usuario@ejemplo.com").apply();
        }

        // UI coins
        tvCoins = findViewById(R.id.coinNumber);
        progressBar = findViewById(R.id.progressBar);



        // RecyclerView
        recyclerView = findViewById(R.id.recyclerPremios);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));

        premios = new ArrayList<>();
        premios.add(new PojoPremio("Viaje en Transporte Público", 50, R.drawable.ic_transport));

        adapter = new PremioAdapter(premios, this);
        recyclerView.setAdapter(adapter);

        // Callback para actualizar coins al canjear
        adapter.setOnCoinsChangedListener(nuevoCoins -> {
            tvCoins.setText(String.valueOf(nuevoCoins));
            progressBar.setProgress(nuevoCoins);
        });
    }
}
