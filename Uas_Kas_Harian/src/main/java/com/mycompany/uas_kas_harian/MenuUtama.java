package com.mycompany.uas_kas_harian;

import java.awt.BorderLayout;
import java.sql.*;
import org.jfree.chart.ChartFactory;
import org.jfree.chart.ChartPanel;
import org.jfree.chart.JFreeChart;
import org.jfree.data.general.DefaultPieDataset;

public class MenuUtama extends javax.swing.JFrame {

    public MenuUtama() {
        initComponents();
        setLocationRelativeTo(null);
        tampilkanGrafik(); // Panggil fungsi grafik saat menu utama terbuka
    }

    private void tampilkanGrafik() {
        int totalPemasukan = 0;
        int totalPengeluaran = 0;

        try {
            Connection conn = Koneksi.getKoneksi();
            String query = "SELECT jenis, SUM(jumlah) AS total FROM transaksi GROUP BY jenis";
            ResultSet rs = conn.createStatement().executeQuery(query);
            
            while (rs.next()) {
                String jenis = rs.getString("jenis");
                int total = rs.getInt("total");
                
                if (jenis.equalsIgnoreCase("Pemasukan")) {
                    totalPemasukan = total;
                } else if (jenis.equalsIgnoreCase("Pengeluaran")) {
                    totalPengeluaran = total;
                }
            }
        } catch (SQLException e) {
            System.out.println("Gagal memuat data grafik: " + e.getMessage());
        }

        if (totalPemasukan == 0 && totalPengeluaran == 0) {
            totalPemasukan = 1; 
        }

        DefaultPieDataset dataset = new DefaultPieDataset();
        dataset.setValue("Pemasukan (Rp " + totalPemasukan + ")", totalPemasukan);
        dataset.setValue("Pengeluaran (Rp " + totalPengeluaran + ")", totalPengeluaran);

        JFreeChart chart = ChartFactory.createPieChart(
                "Ringkasan Arus Kas Keuangan", // Judul Grafik
                dataset,                       // Data
                true,                          // Tampilkan Legend
                true,                          // Tooltips
                false                          // URLs
        );

        ChartPanel chartPanel = new ChartPanel(chart);
        panelGrafik.removeAll();
        panelGrafik.add(chartPanel, BorderLayout.CENTER);
        panelGrafik.validate(); 
    }

    @SuppressWarnings("unchecked")
    // <editor-fold defaultstate="collapsed" desc="Generated Code">//GEN-BEGIN:initComponents
    private void initComponents() {

        btnKategori = new javax.swing.JButton();
        btnDompet = new javax.swing.JButton();
        btnTransaksi = new javax.swing.JButton();
        jLabel1 = new javax.swing.JLabel();
        panelGrafik = new javax.swing.JPanel();

        setDefaultCloseOperation(javax.swing.WindowConstants.EXIT_ON_CLOSE);

        btnKategori.setText("Kategori");
        btnKategori.addActionListener(this::btnKategoriActionPerformed);

        btnDompet.setText("Dompet");
        btnDompet.addActionListener(this::btnDompetActionPerformed);

        btnTransaksi.setText("Transaksi");
        btnTransaksi.addActionListener(this::btnTransaksiActionPerformed);

        jLabel1.setText("Menu Utama");

        panelGrafik.setPreferredSize(new java.awt.Dimension(180, 200));
        panelGrafik.setLayout(new java.awt.BorderLayout());

        javax.swing.GroupLayout layout = new javax.swing.GroupLayout(getContentPane());
        getContentPane().setLayout(layout);
        layout.setHorizontalGroup(
            layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
            .addGroup(layout.createSequentialGroup()
                .addGap(18, 18, 18)
                .addComponent(panelGrafik, javax.swing.GroupLayout.PREFERRED_SIZE, javax.swing.GroupLayout.DEFAULT_SIZE, javax.swing.GroupLayout.PREFERRED_SIZE)
                .addGroup(layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
                    .addGroup(layout.createSequentialGroup()
                        .addGap(27, 27, 27)
                        .addComponent(jLabel1))
                    .addGroup(layout.createSequentialGroup()
                        .addGap(18, 18, 18)
                        .addGroup(layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
                            .addComponent(btnDompet, javax.swing.GroupLayout.PREFERRED_SIZE, 93, javax.swing.GroupLayout.PREFERRED_SIZE)
                            .addComponent(btnKategori, javax.swing.GroupLayout.PREFERRED_SIZE, 93, javax.swing.GroupLayout.PREFERRED_SIZE)
                            .addComponent(btnTransaksi, javax.swing.GroupLayout.PREFERRED_SIZE, 93, javax.swing.GroupLayout.PREFERRED_SIZE))))
                .addContainerGap(21, Short.MAX_VALUE))
        );
        layout.setVerticalGroup(
            layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
            .addGroup(layout.createSequentialGroup()
                .addGap(34, 34, 34)
                .addGroup(layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
                    .addGroup(layout.createSequentialGroup()
                        .addComponent(jLabel1)
                        .addGap(18, 18, 18)
                        .addComponent(btnKategori, javax.swing.GroupLayout.PREFERRED_SIZE, 30, javax.swing.GroupLayout.PREFERRED_SIZE)
                        .addGap(18, 18, 18)
                        .addComponent(btnDompet, javax.swing.GroupLayout.PREFERRED_SIZE, 29, javax.swing.GroupLayout.PREFERRED_SIZE)
                        .addGap(18, 18, 18)
                        .addComponent(btnTransaksi, javax.swing.GroupLayout.PREFERRED_SIZE, 31, javax.swing.GroupLayout.PREFERRED_SIZE))
                    .addComponent(panelGrafik, javax.swing.GroupLayout.PREFERRED_SIZE, javax.swing.GroupLayout.DEFAULT_SIZE, javax.swing.GroupLayout.PREFERRED_SIZE))
                .addContainerGap(15, Short.MAX_VALUE))
        );

        pack();
    }// </editor-fold>//GEN-END:initComponents

    private void btnDompetActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnDompetActionPerformed
        new tampilDompetFrame().setVisible(true);
        this.dispose();       
    }//GEN-LAST:event_btnDompetActionPerformed

    private void btnKategoriActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnKategoriActionPerformed
        new tampilKategoriFrame().setVisible(true);
        this.dispose();   
    }//GEN-LAST:event_btnKategoriActionPerformed

    private void btnTransaksiActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnTransaksiActionPerformed
        new tampilTransaksiFrame().setVisible(true);
        this.dispose();    }//GEN-LAST:event_btnTransaksiActionPerformed

    /**
     * @param args the command line arguments
     */
    public static void main(String args[]) {
        try {
            com.formdev.flatlaf.FlatDarkLaf.setup();
        } catch(Exception ex) {
            System.err.println("Gagal memuat tema");
        }
        java.awt.EventQueue.invokeLater(() -> {
            new MenuUtama().setVisible(true);
        });
    }

    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JButton btnDompet;
    private javax.swing.JButton btnKategori;
    private javax.swing.JButton btnTransaksi;
    private javax.swing.JLabel jLabel1;
    private javax.swing.JPanel panelGrafik;
    // End of variables declaration//GEN-END:variables
}
