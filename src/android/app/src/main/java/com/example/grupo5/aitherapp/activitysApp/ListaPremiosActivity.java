package com.example.grupo5.aitherapp.activitysApp;
import com.example.grupo5.aitherapp.pojos.PojoPremio;
import android.os.Bundle;
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

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_lista_premios);

        recyclerView = findViewById(R.id.recyclerPremios);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));

        premios = new ArrayList<>();

        premios.add(new PojoPremio("Viaje en Transporte Público", 10, R.drawable.ic_bus));
        premios.add(new PojoPremio("Botella Aither", 30, R.drawable.ic_botella));

        adapter = new PremioAdapter(premios, this);
        recyclerView.setAdapter(adapter);
    }
}
