<?php
/**
 * Admin Users Management – Secura
 * User management: view, edit, delete users
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/admin/auth.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (empty($_SESSION['user_avatar'])) {
    $pdo = getDBConnection('secura');
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $avatar = $stmt->fetchColumn();
    if ($avatar) {
        $_SESSION['user_avatar'] = $avatar;
    }
}

$message = '';
$message_type = 'success';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pdo = getDBConnection('secura');

    if ($_POST['action'] === 'delete_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];

        // Prevent admin from deleting themselves
        if ($user_id === $_SESSION['user_id']) {
            $message = 'Vous ne pouvez pas vous supprimer vous-même.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = 'Utilisateur supprimé avec succès.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Erreur lors de la suppression de l\'utilisateur.';
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'update_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_role = $_POST['role'] ?? 'user';

        if (empty($new_username) || empty($new_email)) {
            $message = 'Nom d\'utilisateur et email requis.';
            $message_type = 'error';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email invalide.';
            $message_type = 'error';
        } elseif (!in_array($new_role, ['admin', 'user'])) {
            $message = 'Rôle invalide.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_role, $user_id]);
                $message = 'Utilisateur mis à jour avec succès.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Erreur lors de la mise à jour de l\'utilisateur.';
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'create_user') {
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_password = $_POST['password'] ?? '';
        $new_role = $_POST['role'] ?? 'user';

        if (empty($new_username) || empty($new_email) || empty($new_password)) {
            $message = 'Tous les champs sont requis.';
            $message_type = 'error';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email invalide.';
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'Le mot de passe doit contenir au moins 6 caractères.';
            $message_type = 'error';
        } elseif (!in_array($new_role, ['admin', 'user'])) {
            $message = 'Rôle invalide.';
            $message_type = 'error';
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$new_username, $new_email]);
                if ($stmt->rowCount() > 0) {
                    $message = 'Nom d\'utilisateur ou email déjà utilisé.';
                    $message_type = 'error';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$new_username, $new_email, $hashed_password, $new_role]);
                    $message = 'Utilisateur créé avec succès.';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $message = 'Erreur lors de la création de l\'utilisateur.';
                $message_type = 'error';
            }
        }
    }
}

// Get all users
$pdo = getDBConnection('secura');
$stmt = $pdo->prepare("SELECT id, username, email, role, created_at, avatar FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title  = 'Gestion des Utilisateurs';
$page_heading = '👥 Gestion des Utilisateurs';
$active_menu = 'users';
$extra_css   = ['css/admin-users-page.css'];

require_once __DIR__ . '/includes/admin/layout_start.php';
?>
  <div class="dash-section">

    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <input type="text" id="userSearch" placeholder="Rechercher un utilisateur..." class="form-input" style="padding: 10px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
        <button type="button" class="btn btn-primary" id="btnOpenCreate">
          ➕ Ajouter un Utilisateur
        </button>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $message_type; ?>">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <div class="dash-section">
      <div class="users-container">
        <div class="users-header">
          <span>Avatar</span>
          <span>Nom d'utilisateur</span>
          <span>Email</span>
          <span>Rôle</span>
          <span>Date d'inscription</span>
          <span style="text-align:right">Actions</span>
        </div>
        <?php foreach ($users as $user): ?>
          <div class="user-row">
            <div class="user-avatar-cell">
              <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/images/default-avatar.svg'; ?>"
                   alt="Avatar" class="user-avatar-small">
            </div>
            <span class="col-username"><?php echo htmlspecialchars($user['username']); ?></span>
            <span class="col-email"><?php echo htmlspecialchars($user['email']); ?></span>
            <span>
              <span class="role-badge role-<?php echo $user['role']; ?>">
                <?php echo $user['role'] === 'admin' ? 'Admin' : 'Utilisateur'; ?>
              </span>
            </span>
            <span class="col-date"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
            <span class="col-actions">
              <button type="button" class="btn btn-success btn-sm" data-action="edit"
                data-id="<?php echo (int)$user['id']; ?>"
                data-username="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>"
                data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"
                data-role="<?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>">
                ✏️ Modifier
              </button>
              <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                <button type="button" class="btn btn-danger btn-sm" data-action="delete"
                  data-id="<?php echo (int)$user['id']; ?>"
                  data-username="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>">
                  🗑️ Supprimer
                </button>
              <?php endif; ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

<?php require_once __DIR__ . '/includes/admin/layout_end.php'; ?>

  <!-- Create User Modal -->
  <div class="modal" id="createModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Ajouter un Utilisateur</h2>
        <button type="button" class="modal-close" data-modal-close="createModal">&times;</button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="create_user">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label" for="create_username">Nom d'utilisateur</label>
            <input type="text" id="create_username" name="username" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="create_email">Email</label>
            <input type="email" id="create_email" name="email" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="create_password">Mot de passe</label>
            <input type="password" id="create_password" name="password" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="create_role">Rôle</label>
            <select id="create_role" name="role" class="form-select">
              <option value="user">Utilisateur</option>
              <option value="admin">Administrateur</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" data-modal-close="createModal">Annuler</button>
          <button type="submit" class="btn-submit" style="background: var(--cyber-danger);">Supprimer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div class="modal" id="editModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Modifier l'Utilisateur</h2>
        <button type="button" class="modal-close" data-modal-close="editModal">&times;</button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="update_user">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label" for="edit_username">Nom d'utilisateur</label>
            <input type="text" id="edit_username" name="username" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit_email">Email</label>
            <input type="email" id="edit_email" name="email" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit_role">Rôle</label>
            <select id="edit_role" name="role" class="form-select">
              <option value="user">Utilisateur</option>
              <option value="admin">Administrateur</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" data-modal-close="editModal">Annuler</button>
          <button type="submit" class="btn-submit">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Confirmer la suppression</h2>
        <button type="button" class="modal-close" data-modal-close="deleteModal">&times;</button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" id="delete_user_id">
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="delete_username"></strong> ?</p>
          <p class="text-danger">Cette action est irréversible.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" data-modal-close="deleteModal">Annuler</button>
          <button type="submit" class="btn-submit">Créer</button>
        </div>
      </form>
    </div>
  </div>

<?php
$extra_js = ['js/admin-users.js'];
require_once __DIR__ . '/includes/admin/layout_footer.php';
?>