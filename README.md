# PAGE - Projeto Integrador DSM Módulo 3

Sistema de Gerenciamento de Planos de Aula e Registro de Aulas  
Faculdade de Itapira - Curso de Desenvolvimento de Sistemas para a Internet (DSM)  
Módulo 3

## Integrantes

- Cauã Araujo Vaz de Lima
- Inácio Custódio Silva
- João Gabriel Marreiro de Souza
- Marco Antônio Bubola
- Rafael Henrique Zanesco

---

## Descrição do Projeto

O **PAGE** é um sistema web desenvolvido como Projeto Integrador do curso de DSM, com o objetivo de facilitar o gerenciamento de planos de aula, disciplinas e o registro de aulas ministradas por professores. O sistema contempla diferentes perfis de usuário, cada um com permissões específicas, promovendo organização, transparência e praticidade no ambiente acadêmico.

---

## Funcionalidades

### 1. Perfis de Usuário e Permissões

#### Coordenador/Admin
- Cria, edita e exclui disciplinas.
- Cria, edita e exclui turmas, vinculando múltiplas disciplinas a cada turma.
- Cria, edita e exclui planos de aula, vinculando-os a turmas e disciplinas.
- Gerencia capítulos e tópicos de cada plano de aula.
- Visualiza todas as turmas, disciplinas, planos, capítulos e tópicos.
- Visualiza e gerencia usuários (apenas admin).
- Visualiza últimos planos criados e estatísticas do sistema.

#### Professor
- Visualiza as disciplinas e planos de aula disponíveis para suas turmas.
- Visualiza capítulos e tópicos dos planos.
- Registra aulas realizadas, marcando tópicos ministrados e podendo adicionar tópicos extras.
- Visualiza histórico de aulas ministradas.

---

### 2. Planejamento das Telas (Homes)

#### Home do Coordenador/Admin
- Cards de resumo (turmas, disciplinas, planos, usuários).
- Carrossel de disciplinas em destaque.
- Listagem de turmas, disciplinas e planos em cards, com status e ações rápidas.
- Seção de últimos planos de aula criados.
- Acesso ao gerenciamento de usuários (admin).

#### Home do Professor
- Cards de disciplinas e planos disponíveis.
- Visualização dos capítulos e tópicos de cada plano.
- Botão para registrar aula (seleção de disciplina, plano, capítulo, tópicos e tópico extra).
- Histórico de aulas ministradas.

#### Outras Telas
- **Disciplinas:** CRUD completo em cards.
- **Turmas:** CRUD completo em cards, seleção múltipla de disciplinas.
- **Planos de Aula:** Cards detalhados, filtragem por turma, capítulos e status.
- **Detalhe do Plano:** Visualização e gerenciamento de capítulos e tópicos.
- **Login/Registro:** Telas modernas, com senha criptografada e opção de mostrar/ocultar senha.
- **Gerenciar Usuários:** (admin) CRUD de usuários do sistema.

---

## Tecnologias Utilizadas

- **PHP** (backend)
- **MySQL** (banco de dados relacional)
- **HTML5, CSS3, JavaScript** (frontend)
- **Bootstrap 5** (componentes visuais e responsividade)
- **Select2** (seleção múltipla de disciplinas)
- **XAMPP** (ambiente de desenvolvimento local)

---

## Estrutura do Projeto

```
Page--PI/
│
├── assets/
│   ├── img/         # Imagens e logo do sistema
│   ├── css/         # Arquivos de estilo customizados
│   └── js/          # Scripts JavaScript customizados
│
├── config/
│   └── conexao.php  # Script de conexão com o banco de dados MySQL
│
├── controllers/     # Lógica de backend (CRUD, autenticação, etc)
│   ├── criar_turma.php           # Criação de turmas
│   ├── editar_turma.php          # Edição de turmas
│   ├── excluir_turma.php         # Exclusão de turmas
│   ├── criar_disciplina.php      # Criação de disciplinas
│   ├── editar_disciplina.php     # Edição de disciplinas
│   ├── excluir_disciplina.php    # Exclusão de disciplinas
│   ├── criar_plano.php           # Criação de planos de aula
│   ├── editar_plano.php          # Edição de planos de aula
│   ├── excluir_plano.php         # Exclusão de planos de aula
│   ├── criar_capitulo.php        # Criação de capítulos
│   ├── editar_capitulo.php       # Edição de capítulos
│   ├── excluir_capitulo.php      # Exclusão de capítulos
│   ├── criar_topico.php          # Criação de tópicos
│   ├── editar_topico.php         # Edição de tópicos
│   ├── excluir_topico.php        # Exclusão de tópicos
│
├── views/           # Telas do sistema (frontend)
│   ├── login.php                # Tela de login
│   ├── registro.php             # Tela de cadastro de usuário
│   ├── home_coordenador.php     # Dashboard do coordenador/admin
│   ├── home_professor.php       # Dashboard do professor
│   ├── disciplinas.php          # CRUD de disciplinas
│   ├── turmas.php               # CRUD de turmas
│   ├── planos.php               # Listagem de planos de aula
│   ├── plano_detalhe.php        # Detalhamento de plano de aula (capítulos/tópicos)
│   ├── gerenciar_usuarios.php   # Gerenciamento de usuários (admin)
│   ├── notificacao.php          # Componente de notificações
│   ├── navbar.php               # Barra de navegação
│   └── logout.php               # Logout do sistema
│
├── README.md        # Documentação do projeto
└── ... (outros arquivos e pastas auxiliares)
```

- **controllers/**: Cada arquivo é responsável por uma ação específica (criar, editar, excluir) de cada entidade do sistema (turma, disciplina, plano, capítulo, tópico).
- **views/**: Cada arquivo representa uma tela ou componente visual do sistema, seguindo o padrão de responsividade e usabilidade.
- **assets/**: Recursos estáticos (imagens, CSS, JS).
- **config/**: Configuração de conexão com o banco de dados.

---

## Como rodar o projeto

1. Clone o repositório ou baixe os arquivos.
2. Importe o banco de dados MySQL usando o arquivo SQL fornecido.
3. Configure o arquivo `/config/conexao.php` com os dados do seu banco.
4. Inicie o XAMPP e acesse o sistema via navegador:  
   `http://localhost/Page--PI/`

---

**PAGE** - Projeto Integrador DSM Módulo 3  
Faculdade de Itapira