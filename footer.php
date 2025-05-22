</div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <footer class="main-footer">
            <strong>AGTool Finance &copy; 2025</strong> - Gestione Finanze Personali
            <div class="float-right d-none d-sm-inline-block">
                <b>Versione</b> 1.0.0
            </div>
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- Modal Aggiungi Transazione -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" role="dialog" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">Aggiungi Transazione</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="process_transaction.php" method="post" id="transaction-form">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="transaction-type">Tipo</label>
                            <select id="transaction-type" name="type" class="form-control" required>
                                <option value="entrata">Entrata</option>
                                <option value="uscita">Uscita</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transaction-amount">Importo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">€</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" id="transaction-amount" name="amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="transaction-description">Descrizione</label>
                            <input type="text" id="transaction-description" name="description" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="transaction-category">Categoria</label>
                            <select id="transaction-category" name="category" class="form-control" required>
                                <!-- Le categorie saranno caricate dinamicamente in base al tipo selezionato -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transaction-date">Data</label>
                            <input type="date" id="transaction-date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- ChartJS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
