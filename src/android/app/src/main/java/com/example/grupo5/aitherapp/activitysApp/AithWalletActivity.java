package com.example.grupo5.aitherapp.activitysApp;


import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.pojos.PojoPremio;

import java.util.ArrayList;
import java.util.List;

public class AithWalletActivity extends AppCompatActivity {



    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_aithwallet);

        ImageView backArrow = findViewById(R.id.imgBackArrow);
        backArrow.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                finish();
            }
        });

        Button btnListaPremios = findViewById(R.id.btnListaPremios);
        btnListaPremios.setOnClickListener(v -> {
            Intent intent = new Intent(AithWalletActivity.this, ListaPremiosActivity.class);
            startActivity(intent);
        });

    }}